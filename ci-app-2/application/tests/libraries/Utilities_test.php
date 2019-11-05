<?php

class Utilities_test extends TestCase
{
    public function setUp()
    {
        $this->resetInstance();
        $this->CI->load->library('Utilities');
        $this->obj = $this->CI->utilities;

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

    public function test_balance_not_in_memcache()
    {
        $data = false;

        $this->assertTrue($this->obj->checkCompanyBalance($data, '1_call'));
    }

    public function test_balance_in_memcache_but_staus_false()
    {
        $data = json_decode('{"status":"success","result":{"1_call":{"free":6600,"actual":{"free":"6600","additional":"0"},"name":"Incoming (Call)","unit":"Minute(s)","status":0,"display_status":1,"display_type":"1","use":0,"service_live_status":"2","leg_b":{"min":0,"cost":0}}}}');

        $this->assertFalse($this->obj->checkCompanyBalance($data, '1_call'));
    }

    public function test_balance_in_memcache_status_true()
    {
        $data = json_decode('{"status":"success","result":{"1_call":{"free":6600,"actual":{"free":"6600","additional":"0"},"name":"Incoming (Call)","unit":"Minute(s)","status":"1","display_status":1,"display_type":"1","use":0,"service_live_status":"2","leg_b":{"min":0,"cost":0}}}}');

        $this->assertTrue($this->obj->checkCompanyBalance($data, '1_call'));
    }

    public function test_balance_in_memcache_service_live_status_not_1_or_2()
    {
        $data = json_decode('{"status":"success","result":{"1_call":{"free":6600,"actual":{"free":"6600","additional":"0"},"name":"Incoming (Call)","unit":"Minute(s)","status":"1","display_status":1,"display_type":"1","use":0,"service_live_status":"4","leg_b":{"min":0,"cost":0}}}}');

        $this->assertTrue($this->obj->checkCompanyBalance($data, '1_call'));
    }

    public function test_service_live_status_not_1_or_2_left_less_than_10()
    {
        $data = json_decode('{"status":"success","result":{"1_call":{"free":60,"actual":{"free":"6600","additional":"0"},"name":"Incoming (Call)","unit":"Minute(s)","status":"1","display_status":1,"display_type":"1","use":50,"service_live_status":"4","leg_b":{"min":0,"cost":0}}}}');

        $this->assertFalse($this->obj->checkCompanyBalance($data, '1_call'));
    }

    public function test_service_live_status_not_1_or_2_left_more_than_10()
    {
        $data = json_decode('{"status":"success","result":{"1_call":{"free":60,"actual":{"free":"6600","additional":"0"},"name":"Incoming (Call)","unit":"Minute(s)","status":"1","display_status":1,"display_type":"1","use":49,"service_live_status":"4","leg_b":{"min":0,"cost":0}}}}');

        $this->assertTrue($this->obj->checkCompanyBalance($data, '1_call'));
    }

    public function test_miss_dial_check_api_response_is_false()
    {

        $this->obj->engine->api_connector = new Mock_Libraries_ApiConnector("1_missdial");
        $this->assertFalse($this->obj->checkMissDial());
    }
    public function test_lines_available()
    {
        $this->obj->engine->dest = '919873832455';
        $this->obj->engine->api_connector = new Mock_Libraries_ApiConnector("1_lines");
        $this->assertTrue($this->obj->linesAvailable(1));
    }

    public function test_clid_blacklisted()
    {
        $this->obj->engine->api_connector = new Mock_Libraries_ApiConnector("check_blacklisted");
        $this->assertEquals(1, $this->obj->isClidBlackListed(8527384897));
    }
    public function test_clid_not_blacklisted()
    {
        $this->obj->engine->api_connector = new Mock_Libraries_ApiConnector("check_not_blacklisted");
        $this->assertEquals(0, $this->obj->isClidBlackListed(7290097286));
    }

}
