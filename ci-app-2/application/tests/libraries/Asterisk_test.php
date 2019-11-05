<?php

class Asterisk_test extends TestCase
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
            public function __call($name, $val)
            {
                echo $name . " - " . json_encode($val) . "\n";

                if ($name == 'get_data') {
                    return array('result' => '1');
                }
            }
        };

        $this->CI->config->set_item('sound_files', __DIR__.'/');
        $this->CI->config->set_item('ulaw_path', __DIR__.'/');
        $this->CI->load->library('Asterisk');
        $this->obj = $this->CI->asterisk;
    }

    public function test_playAndGetData_no_file()
    {
        $r = $this->obj->playAndGetData(false, 30, 2);
        $this->assertFalse($r);
    }
    public function test_playAndGetData_file_does_not_exist()
    {
        $r = $this->obj->playAndGetData('a', 30, 2);
        $this->assertContains("Sound file does not exist...." . $this->CI->config->item('sound_files') . 'a', $this->CI->messages);
    }
    public function test_playAndGetData()
    {
        $r = $this->obj->playAndGetData('a.ulaw', 30, 2);
        $this->assertEquals('1', $r);
    }
    public function test_getPlayFiles()
    {
        $this->CI->load->helper('myoperator_helper');
        $r = $this->obj->getPlayFiles(array('common' => array('filename' => 'a.ulaw')));
        $this->assertEquals($this->CI->config->item('ulaw_path') . 'a',$r);
    }

    public function test_getPlayFiles_no_value()
    {
        $this->CI->load->helper('myoperator_helper');
        $r = $this->obj->getPlayFiles(array());
        $this->assertFalse($r);
    }
    
}
