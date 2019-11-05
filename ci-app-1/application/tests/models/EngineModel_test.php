<?php

class EngineModel_test extends TestCase
{
    public function setUp()
    {
        $this->resetInstance();
        $this->CI->load->library('Redis');
        $this->CI->load->library('Utilities');
        $this->CI->load->model('EngineModel');
        $this->obj = $this->CI->EngineModel;

        // load database
        $this->CI->load->database();
        // load agi mock
        $this->CI->load->add_package_path(APPPATH . 'third_party/agi')->library('AGI', false, 'agi');

        $this->obj->dest = '919212992129';
        $this->obj->clid = '8527384897';
        $this->obj->clid_raw = '8527384897';
        $this->obj->uid = uniqid();
        $this->obj->rdnis = "unknown";
        $this->obj->company_id = 1;
        $this->obj->company = $this->obj->getCompanyByNumber();
        $this->obj->company_id = $this->obj->company['id'];
        
        $this->CI->agi->set_variable('CHANNEL', 'DAHDI/g1/08527384897');
        $this->CI->config->set_item('server', 'dummy');
        $this->CI->obd = $this->CI->obd_call_uid = false;
    }

    public function test_getCompanyByNumber()
    {
        $this->assertArrayHasKey('id', $this->obj->company);
        $this->assertEquals(1, $this->obj->company['id']);
    }

    public function test_saveAllCaller_without_allcaller_no_obd()
    {
        $allcaller = $this->obj->saveAllCaller();
        $this->assertArrayHasKey('uid', $allcaller);
    }

    public function test_save_allcaller_without_allcaller_but_obd()
    {

        $allcaller = $this->obj->saveAllCaller(false, true);
        $this->assertArrayHasKey('uid', $allcaller);
        $this->assertEquals(1, $allcaller['company_id']);
    }

    public function test_isClidBlackListed(){
        
    }

    public function test_contactInCompany()
    {   
        $this->obj->company = $this->obj->getCompanyByNumber();
        $this->obj->company_id = $this->obj->company['id'];

        $this->obj->api_connector = new Mock_Libraries_ApiConnector("contact_fetch");
        
        $this->assertArrayHasKey('number', $this->obj->contactInCompany('+91@7979867494'));
    }
}
