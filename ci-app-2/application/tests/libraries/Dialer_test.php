<?php

class Dialer_test extends TestCase
{
    public function setUp()
    {
        $this->resetInstance();

        $this->CI->uid = "t." . uniqid();
        $this->CI->company_id = '1';
        $this->CI->ivr = ['ivr_id' => md5('123456')];
        $this->CI->ivr_id = $this->CI->ivr['ivr_id'];

        $this->CI->messages = array();

        $this->CI->logger = new class

        {
            public function __construct()
            {
                $this->engine = &get_instance();
            }
            public function logMe($data, $source = false, $type = 'info')
            {
                echo $type . '-' . json_encode($data) . "\n";
                $this->engine->messages[] = $data;
            }
        };

        $this->CI->agi = new class

        {
            private $vars = array();
            public function get_variable($var)
            {
                return array('data' => @$this->vars[$var]);
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

        $this->CI->agi->set_variable('DESTNUMBER', '919212992129');

        $this->CI->config->set_item('sound_files', __DIR__ . '/');
        $this->CI->config->set_item('ulaw_path', __DIR__ . '/');
        $this->CI->load->library('Dialer');
        $this->obj = $this->CI->dialer;
    }
    public function test_getMask_not_web_initiated_call()
    {
        $this->CI->agi->set_variable('CALLER_NUM_ENGINE', json_encode(array('number' => '7290097286')));
        $r = $this->obj->getMask();
        $this->assertEquals('7290097286', $r);
    }

    // anonymous
    public function test_getMask_caller_number_not_detected_and_call_received_at_mobile()
    {
        $this->obj->received_at = '1';
        $r = $this->obj->getMask();
        $this->assertEquals('919212992129', $r);
    }
    public function test_getMask_caller_number_not_detected_and_call_received_at_web()
    {
        $this->obj->received_at = '2';
        $r = $this->obj->getMask();
        $this->assertEquals('919212992129', $r);
    }
    public function test_getMask_call_initiated_from_web()
    {
        $this->CI->agi->set_variable('WEB_INITIATED', true);
        $this->obj->received_at = '1';
        $r = $this->obj->getMask();
        $this->assertEquals('919212992129', $r);
    }
}
