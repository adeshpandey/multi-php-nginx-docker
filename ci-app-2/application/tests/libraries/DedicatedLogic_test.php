<?php

class DedicatedLogic_test extends TestCase
{
    public function setUp()
    {
        $this->resetInstance();
        $this->CI->load->library('DedicatedLogic', false, 'dedicated_logic');
        $this->CI->load->helper('myoperator_helper');
        loadMyOpConfig();
        
        $this->obj = $this->CI->dedicated_logic;

        $this->obj->engine->logger = new class

        {
            public function logMe($data, $source = false, $type = 'info')
            {
                echo $type . '-' . json_encode($data) . "\n";
            }
        };

        $this->obj->engine->agi = new class

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
                echo "\n".$name . " - " . json_encode($val) . "\n";

                if ($name == 'get_data') {
                    return array('result' => '1');
                }
            }
        };

        $this->obj->engine->dest = '919873832455';
        $this->obj->engine->agi->set_variable('DESTNUMBER', '919873832455');
        $this->obj->engine->load->library('Redis');
    }
    public function test_fetchCallerId()
    {
        $this->obj->engine->agi->set_variable('MASK_NUMBER', '2249280797');
        $this->assertEquals('2249280797', $this->obj->fetchCallerId('1'));
    }

    public function test_fetchCallerId_if_wrong_outgoing()
    {
        $this->obj->engine->agi->set_variable('MASK_NUMBER', '2249280799');
        $this->assertFalse($this->obj->fetchCallerId('1'));
    }

    public function test_fetchCallerId_if_no_mask()
    {
        $this->assertFalse($this->obj->fetchCallerId('1'));
    }
}
