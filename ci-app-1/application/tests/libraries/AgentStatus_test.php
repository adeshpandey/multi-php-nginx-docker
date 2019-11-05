<?php

class AgentStatus_test extends TestCase
{
    public function setUp()
    {
        $this->resetInstance();
        $this->CI->load->library('AgentStatus');
        $this->obj = $this->CI->agentstatus;

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

        $this->obj->engine->load->library('ApiConnector', false, 'api_connector');

        $this->obj->engine->config->set_item('api_path', 'http://newstageapi.voicetree.info/');
        $this->obj->engine->config->set_item('myop_memcache_token', 'j6h@!!3K(x*');
        $this->obj->engine->config->set_item('server', 'test');
        
        $this->obj->engine->company_id = '1';
        $this->obj->engine->ivr = ['ivr_id' => md5('123456')];
        $this->obj->engine->uid = uniqid();
        
    }
    public function testAgentBusyOnDid()
    {
        $active_calls = json_decode('{
            "status": "success",
            "result": {
                "7c5b6bc0fe21dbf13fc9e797757c902e": [
                    {
                        "did": "919873832455",
                        "clid": "917290097286",
                        "event": 1,
                        "uid": "s6.1539605334.152909",
                        "duration": "1",
                        "dial_string": "CONNECTING/918527384897"
                    }
                ],
                "6f7248e3a827b919611e9e32e2542e25": false
            }
        }');

        $agent_number = '8527384897';
        $actual = $this->obj->checkAgentStatus($active_calls, $agent_number);
        $this->assertTrue($actual);
    }

    public function testAgentBusyBecauseDialedOnService()
    {
        $active_calls = json_decode('{
            "status": "success",
            "result": {
                "7c5b6bc0fe21dbf13fc9e797757c902e": [
                    {
                        "did": "919873832455",
                        "clid": "918527384897",
                        "event": 1,
                        "uid": "s6.1539605334.152909",
                        "duration": "1",
                        "dial_string": "CONNECTING"
                    }
                ],
                "6f7248e3a827b919611e9e32e2542e25": false
            }
        }');

        $agent_number = '8527384897';
        $actual = $this->obj->checkAgentStatus($active_calls, $agent_number);
        $this->assertTrue($actual);
    }

    public function testAgentFree()
    {
        $active_calls = json_decode('{
            "status": "success",
            "result": {
                "7c5b6bc0fe21dbf13fc9e797757c902e": false,
                "6f7248e3a827b919611e9e32e2542e25": false
            }
        }');

        $agent_number = '8527384897';
        $actual = $this->obj->checkAgentStatus($active_calls, $agent_number);
        $this->assertFalse($actual);
    }
    public function testAgentFreeIfApiFailed()
    {
        $active_calls = json_decode(false);

        $agent_number = '8527384897';
        $actual = $this->obj->checkAgentStatus($active_calls, $agent_number);
        $this->assertFalse($actual);
    }

    public function testAgentFreeIfApiDidNotReturnResult()
    {
        $active_calls = json_decode('{
            "status": "success",
            "result": false
        }');

        $agent_number = '8527384897';
        $actual = $this->obj->checkAgentStatus($active_calls, $agent_number);
        $this->assertFalse($actual);
    }

    public function testAgentFreeIfWrongApiData()
    {
        $active_calls = json_decode('{
            "status": "success",
            "result": "abc"
        }');

        $agent_number = '8527384897';
        $actual = $this->obj->checkAgentStatus($active_calls, $agent_number);
        $this->assertFalse($actual);
    }

    public function testGetAgentsStatus()
    {
        $agents = json_decode('{"5437b9d431e0d827": {"uuid": "5437b9d431e0d827","contact": "8750415919","contact_2": "","contact_country": "+91","contact_2_country": "","contact_type": "mobile","contact_type_2": "mobile","extension": "11","email": "shikha.negi@myoperator.co","user_type": "1","linked_companies": [{"display_number": "911145823001"},{"display_number": "911130323999"},{"display_number": "914045965769"},{"display_number": "914045965722"},{"display_number": "914045965743"},{"display_number": "914045967168"},{"display_number": "914045965773"},{"display_number": "914045967120"},{"display_number": "914045967038"},{"display_number": "914045967192"},{"display_number": "914045967153"},{"display_number": "914045965736"},{"display_number": "914045965827"},{"display_number": "914045967019"}],"timing_manager": [{"day": 127,"start_time": "04:30:00","end_time": "14:00:00"}]},"5b7cff6c0ca48766": {"uuid": "5b7cff6c0ca48766","contact": "9718710020","contact_2": "0000000","contact_country": "+91","contact_2_country": "+91","contact_type": "mobile","contact_type_2": "mobile","extension": "13","email": "monika.saini@myoperator.co","user_type": "1","linked_companies": [{"display_number": "914045965750"},{"display_number": "911145823001"},{"display_number": "914045965858"},{"display_number": "914045967191"},{"display_number": "18001025153"},{"display_number": "18001202484"},{"display_number": "18001215686"},{"display_number": "914045965881"},{"display_number": "914045965747"},{"display_number": "918448444232"}],"timing_manager": [{"day": 126,"start_time": "18:30:00","end_time": "18:30:00"}]}}', true);
        $expected = array('5437b9d431e0d827' => false, '5b7cff6c0ca48766' => false);
        $this->assertEquals($expected, $this->obj->getAgentsStatus($agents));
    }
}
