<?php

class ApiConnector_test extends TestCase
{
    public function setUp()
    {
        $this->resetInstance();
        $this->CI->load->library('ApiConnector');
        $this->obj = $this->CI->apiconnector;

        $this->obj->engine->logger = new class

        {
            public function logMe($data, $source = false, $type = 'info')
            {
                echo $type . '-' . json_encode($data) . "\n";
            }
        };

        $this->obj->engine->agi = new class

        {
            public function __call($name, $val)
            {
                echo $name . " - " . json_encode($val) . "\n";
            }
        };

        $this->obj->engine->company_id = '1';
        $this->obj->engine->ivr = ['ivr_id' => md5('123456')];
        $this->obj->engine->uid = uniqid();
        $this->obj->engine->config->set_item('server', 'test');
        $this->obj->data["key"] = "1_call";
    }

    public function test_handle_http_status_code()
    {

    }
}
