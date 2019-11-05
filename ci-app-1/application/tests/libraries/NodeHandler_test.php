<?php

class NodeHandler_test extends TestCase
{
    public function setUp()
    {
        $this->resetInstance();

        $this->CI->uid = "t." . uniqid();
        $this->CI->company_id = '1';
        $this->CI->ivr = ['ivr_id' => md5('123456')];
        $this->CI->ivr_id = $this->CI->ivr['ivr_id'];

        $this->CI->api_connector = new Mock_Libraries_ApiConnector('6f7248e3a827b919611e9e32e2542e25');
        $this->CI->messages = array();

        $this->CI->logger = new class

        {
            public function __construct()
            {
                $this->engine = &get_instance();
            }
            public function logMe($data, $source = false, $type = 'info')
            {
                echo "\n" . $type . '-' . json_encode($data) . "\n";
                $this->engine->messages[] = $data;
            }
        };

        $this->CI->agi = new class

        {
            private $vars = array();
            public function get_variable($var)
            {
                return array('data' => @$this->vars[$var], 'code' => 200, 'result' => @$this->vars[$var]);
            }
            public function set_variable($name, $val)
            {
                $this->vars[$name] = $val;
            }
            public function __call($name, $val)
            {
                echo $name . " - " . json_encode($val) . "\n";

                if ($name == 'get_data') {
                    return array('result' => '1');
                }
            }
        };

        $this->CI->dest = '919212992129';
        $this->CI->agi->set_variable('DESTNUMBER', '919212992129');

        $this->CI->config->set_item('sound_files', dirname(__DIR__) . '/');
        $this->CI->config->set_item('ulaw_path', dirname(__DIR__) . '/');
        $this->CI->uid = uniqid();
        $this->CI->clid = '918527384897';
        $this->CI->clid_raw = '8527384897';
        $this->CI->config->set_item('server', 'test');
        $this->CI->rdnis = 'unknown';
        $this->CI->obd = $this->CI->obd_call_uid = '';
        $this->CI->ivr = array(
            'ivr_id' => uniqid(),
            'settings' => array(
                'max_menu_reply' => '2',
                'after_menu_dept' => '',
                'after_menu_number' => '',
            ),
        );

        $this->CI->agi->set_variable('CHANNEL', 'DAHDI/g0/8527384897-34d5');

        $this->CI->load->model('EngineModel', 'model');
        $this->CI->load->library('Asterisk');
        $this->CI->load->library('AgentStatus', false, 'agent_status');
        $this->CI->load->library('PreCaller', false, 'preCaller');
        $this->CI->load->library('NodeProcessor', false, 'node_processor');
        $this->CI->load->library('Redis');
        $this->CI->load->library('Dialer');
        $this->CI->load->database();
        $this->CI->load->library('NodeHandler');

        $this->CI->model->fillUsers();
        $this->CI->users = $this->CI->model->users;

        $this->CI->allcaller = $this->CI->model->saveAllCaller();

        $this->obj = $this->CI->nodehandler;
    }

    public function testMenuNode()
    {
        $this->CI->node_keys = array('_1' => '_15');
        $this->CI->nodes = array('_15' => array(
            'sounds' => array('common' => array('filename' => 'a.ulaw')),
            'node_value' => '_15',
            'settings' => array(
                'timeout' => 15,
                'msg_node' => false,
                'max_digits' => 2,
                'max_menu_reply' => 3,
            ),
        ));

        $node = array(
            'sounds' => array('common' => array('filename' => 'a.ulaw')),
            'node_value' => '_0',
            'settings' => array(
                'timeout' => 15,
                'msg_node' => false,
                'max_digits' => 2,
                'max_menu_reply' => 3,
            ),
        );
        $language = 'common';
        $r = $this->obj->menuHandler($node, $language);
        $expected = array('next_node' => $this->CI->nodes['_15'], 'next_node_type' => '5');
        $this->assertEquals($expected, $r);
    }
    public function testMenuNodeNextNodeNotFound()
    {

        $this->CI->node_keys = array('_2' => '_25');
        $this->CI->nodes = array('_25' => array(
            'sounds' => array('common' => array('filename' => 'a.ulaw')),
            'node_value' => '_25',
            'settings' => array(
                'timeout' => 15,
                'msg_node' => false,
                'max_digits' => 2,
                'max_menu_reply' => 3,
            ),
        ));

        $node = array(
            'sounds' => array('common' => array('filename' => 'a.ulaw')),
            'node_value' => '_0',
            'settings' => array(
                'timeout' => 15,
                'msg_node' => false,
                'max_digits' => 2,
                'max_menu_reply' => 3,
            ),
        );
        $language = 'common';
        $r = $this->obj->menuHandler($node, $language);
        $this->assertFalse($r);
    }

    public function testMessageNode()
    {
        $this->CI->node_keys = array('_2' => '_25');
        $this->CI->nodes = array('_25' => array(
            'sounds' => array('common' => array('filename' => 'a.ulaw')),
            'node_value' => '_25',
            'settings' => array(
                'timeout' => 15,
                'msg_node' => false,
                'max_digits' => 2,
                'max_menu_reply' => 3,
            ),
        ));

        $node = array(
            'sounds' => array('common' => array('filename' => 'a.ulaw')),
            'node_value' => '_0',
            'settings' => array(
                'timeout' => 15,
                'msg_node' => '1',
                'max_digits' => 2,
                'max_menu_reply' => 3,
            ),
        );
        $language = 'common';
        $r = $this->obj->menuHandler($node, $language);
        $this->assertFalse($r);
    }
    // message node will play and will not process any DTMF input
    public function testMessageNodeButIVRHasNextNode()
    {
        $this->CI->node_keys = array('_1' => '_15');
        $this->CI->nodes = array('_15' => array(
            'sounds' => array('common' => array('filename' => 'a.ulaw')),
            'node_value' => '_15',
            'settings' => array(
                'timeout' => 15,
                'msg_node' => false,
                'max_digits' => 2,
                'max_menu_reply' => 3,
            ),
        ));

        $node = array(
            'sounds' => array('common' => array('filename' => 'a.ulaw')),
            'node_value' => '_0',
            'settings' => array(
                'timeout' => 15,
                'msg_node' => '1',
                'max_digits' => 2,
                'max_menu_reply' => 3,
            ),
        );
        $language = 'common';
        $r = $this->obj->menuHandler($node, $language);
        $this->assertFalse($r);
    }
    public function testHandleDepartment()
    {
        $this->CI->uid = uniqid();
        $this->CI->company = array('id' => '1', 'company_name' => 'MyOperator', 'time_zone' => '05:30', 'settings' => array('allow_intl' => false, 'obd' => false, 'dial_optr' => '1'));
        $this->CI->sound_file_name = uniqid();
        $this->CI->language_id = 'common';
        $this->CI->node_keys = array('_1' => '_15');
        $this->CI->nodes = array('_15' => array(
            'sounds' => array('common' => array('filename' => 'a.ulaw')),
            'node_value' => '_15',
            'settings' => array(
                'timeout' => 15,
                'msg_node' => false,
                'max_digits' => 2,
                'max_menu_reply' => 3,
            ),
        ));

        $node = array(
            'id' => '57ee5b81e41dc302_71',
            'sounds' => array('common' => array('filename' => 'a.ulaw')),
            'node_value' => '_0',
            'settings' => array(
                'timeout' => 15,
                'msg_node' => '1',
                'max_digits' => 2,
                'max_menu_reply' => 3,
                'extension_group' => '57ee57f8a23ee700',
                'ring_time' => '15',
                'sticky_agent' => '',
                'hybrid' => '',
                'connection_method' => 'serial',
                'call_recording' => '',
                'mail' => '',
            ),
        );
        $this->CI->alternate = false;
        $language = 'common';
        $r = $this->obj->handleDepartment($node, $language);
        $this->assertFalse($r);
    }
    public function testHandleExtension()
    {
        $this->CI->uid = uniqid();
        $this->CI->company = array('id' => '1', 'company_name' => 'MyOperator', 'time_zone' => '05:30', 'settings' => array('allow_intl' => false, 'obd' => false, 'dial_optr' => '1'));
        $this->CI->sound_file_name = uniqid();
        $this->CI->language_id = 'common';
        $this->CI->node_keys = array('_1' => '_15');
        $this->CI->nodes = array('_15' => array(
            'sounds' => array('common' => array('filename' => 'a.ulaw')),
            'node_value' => '_15',
            'settings' => array(
                'timeout' => 15,
                'msg_node' => false,
                'max_digits' => 2,
                'max_menu_reply' => 3,
            ),
        ));

        $node = array(
            'id' => '57ee5b81e41dc302_71',
            'sounds' => array('common' => array('filename' => 'a.ulaw')),
            'node_value' => '_0',
            'settings' => array(
                'timeout' => 15,
                'msg_node' => '1',
                'max_digits' => 2,
                'max_menu_reply' => 3,
                'extension_group' => '57ee57f8a23ee700',
                'ring_time' => '15',
                'sticky_agent' => '',
                'hybrid' => '',
                'connection_method' => 'serial',
                'call_recording' => '',
                'mail' => '',
            ),
        );
        $this->CI->alternate = false;
        $language = 'common';
        $r = $this->obj->handleExtension($node, $language);
        $this->assertFalse($r);
    }

    public function testCheckVoicemail()
    {
        $this->CI->nodes = array(
            '_15' => array(
                'sounds' => array('common' => array('filename' => 'a.ulaw')),
                'node_value' => '_15',
                'settings' => array(
                    'timeout' => 15,
                    'msg_node' => false,
                    'max_digits' => 2,
                    'max_menu_reply' => 3,
                ),
            ),
            '_1_5' => array(
                'sounds' => array('common' => array('filename' => 'a.ulaw')),
                'node_value' => '_1_5',
                'settings' => array(
                    'timeout' => 15,
                    'msg_node' => false,
                    'max_digits' => 2,
                    'max_menu_reply' => 3,
                ),
            ),
            '_25' => array(
                'sounds' => array('common' => array('filename' => 'a.ulaw')),
                'node_value' => '_25',
                'settings' => array(
                    'timeout' => 15,
                    'msg_node' => false,
                    'max_digits' => 2,
                    'max_menu_reply' => 3,
                ),
            ),
            '_2_2' => array(
                'sounds' => array('common' => array('filename' => 'a.ulaw')),
                'node_value' => '_1_2',
                'settings' => array(
                    'timeout' => 15,
                    'msg_node' => false,
                    'max_digits' => 2,
                    'max_menu_reply' => 3,
                ),
            ),
        );

        $this->CI->node_keys = array('_1' => '_15', '_1_' => '_1_5', '_2_' => '_2_2');

        $node = array(
            'id' => '57ee5b81e41dc302_71',
            'sounds' => array('common' => array('filename' => 'a.ulaw')),
            'node_value' => '_15',
            'settings' => array(
                'timeout' => 15,
                'msg_node' => '1',
                'max_digits' => 2,
                'max_menu_reply' => 3,
                'extension_group' => '57ee57f8a23ee700',
                'ring_time' => '15',
                'sticky_agent' => '',
                'hybrid' => '',
                'connection_method' => 'serial',
                'call_recording' => '',
                'mail' => '',
            ),
        );

        $expected = array('next_node' => $this->CI->nodes['_1_5'], 'next_node_type' => '5');
        $this->assertEquals($expected, $this->obj->checkVoicemail($node, '1'));

        $node = array(
            'id' => '57ee5b81e41dc302_71',
            'sounds' => array('common' => array('filename' => 'a.ulaw')),
            'node_value' => '_25',
            'settings' => array(
                'timeout' => 15,
                'msg_node' => '1',
                'max_digits' => 2,
                'max_menu_reply' => 3,
                'extension_group' => '57ee57f8a23ee700',
                'ring_time' => '15',
                'sticky_agent' => '',
                'hybrid' => '',
                'connection_method' => 'serial',
                'call_recording' => '',
                'mail' => '',
            ),
        );
        $expected = array('next_node' => $this->CI->nodes['_2_2'], 'next_node_type' => '2');
        $this->assertEquals($expected, $this->obj->checkVoicemail($node, '1'));
    }

    public function testcheckVoicemailWithStrictSticky()
    {
        $this->CI->nodes = array(
            '_15' => array(
                'sounds' => array('common' => array('filename' => 'a.ulaw')),
                'node_value' => '_15',
                'settings' => array(
                    'timeout' => 15,
                    'msg_node' => false,
                    'max_digits' => 2,
                    'max_menu_reply' => 3,
                ),
            ),
            '_1_5' => array(
                'sounds' => array('common' => array('filename' => 'a.ulaw')),
                'node_value' => '_1_5',
                'settings' => array(
                    'timeout' => 15,
                    'msg_node' => false,
                    'max_digits' => 2,
                    'max_menu_reply' => 3,
                ),
            ),
            '_25' => array(
                'sounds' => array('common' => array('filename' => 'a.ulaw')),
                'node_value' => '_25',
                'settings' => array(
                    'timeout' => 15,
                    'msg_node' => false,
                    'max_digits' => 2,
                    'max_menu_reply' => 3,
                ),
            ),
            '_2_2' => array(
                'sounds' => array('common' => array('filename' => 'a.ulaw')),
                'node_value' => '_1_2',
                'settings' => array(
                    'timeout' => 15,
                    'msg_node' => false,
                    'max_digits' => 2,
                    'max_menu_reply' => 3,
                ),
            ),
        );

        $this->CI->node_keys = array('_1' => '_15', '_1_' => '_1_5', '_2_' => '_2_2');
        $node = array(
            'id' => '57ee5b81e41dc302_71',
            'sounds' => array('common' => array('filename' => 'a.ulaw')),
            'node_value' => '_15',
            'settings' => array(
                'timeout' => 15,
                'msg_node' => '1',
                'max_digits' => 2,
                'max_menu_reply' => 3,
                'extension_group' => '57ee57f8a23ee700',
                'ring_time' => '15',
                'sticky_agent' => '',
                'hybrid' => '',
                'connection_method' => 'serial',
                'call_recording' => '',
                'mail' => '',
            ),
        );

        $this->assertFalse($this->obj->checkVoicemail($node, '2'));

        $node = array(
            'id' => '57ee5b81e41dc302_71',
            'sounds' => array('common' => array('filename' => 'a.ulaw')),
            'node_value' => '_25',
            'settings' => array(
                'timeout' => 15,
                'msg_node' => '1',
                'max_digits' => 2,
                'max_menu_reply' => 3,
                'extension_group' => '57ee57f8a23ee700',
                'ring_time' => '15',
                'sticky_agent' => '',
                'hybrid' => '',
                'connection_method' => 'serial',
                'call_recording' => '',
                'mail' => '',
            ),
        );

        $expected = array('next_node' => $this->CI->nodes['_2_2'], 'next_node_type' => '2');
        $this->assertEquals($expected, $this->obj->checkVoicemail($node, '2'));
    }

}
