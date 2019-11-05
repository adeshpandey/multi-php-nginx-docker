<?php

class SipIncoming_test extends TestCase
{
    public function setUp()
    {
        $this->resetInstance();
     }

     public function testProcess(){
        $output = $this->request('GET', 'SipIncoming/process');
        print_r($output);
     }
}
