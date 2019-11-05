<?php

class Mock_Libraries_ApiConnector
{
    public $types;
    public function __construct($type)
    {
        $this->types = array(
            '1_misdial' => false,
            '1_web_call' => false,
            '1_lines' => json_decode('{"status" : "success","result":{"1_lines" : {"status" : 1,"free":1} } }'),
            '7c5b6bc0fe21dbf13fc9e797757c902e' => false,
            'check_blacklisted' => json_decode('{"status":"success","response":1}'),
            'check_not_blacklisted' => json_decode('{"status":null,"response":null}'),
            '6f7248e3a827b919611e9e32e2542e25' => false,
            '67addc2ec3a605aea38c2d2afad30b1c' => false,
            'contact_fetch' => '{"status":"success","code":"200","data":[{"_cid":"5c29bebdbbd56674","_ui":"5ab25ca5d4a85198","_ci":"5ab25ca5d1801476","_ct":1546239677,"_ca":1,"_vi":1,"_na":"Prabhat","_at":"","_pm":[{"ky":"skype","vl":"skype.id","d_vl":"skype.id"}],"_cd":[{"_cl":"+917979867494","_cr":"7979867494","_em":""}]}],"total":1}'
        );
    }
    public function fetchAPI($data, $url)
    {
        $d = json_decode($data['data']);

        if (strpos($url, 'contact_fetch') !== false && $d->search_key == '7979867494') {
            return json_decode($this->types['contact_fetch']);
        }

        if (strpos($url, 'check_blacklist') !== false && $d->number == '8527384897') {
            return $this->types['check_blacklisted'];
        }

        if (strpos($url, 'check_blacklist') !== false && $d->number == '7290097286') {
            return $this->types['check_not_blacklisted'];
        }
        if (is_array($d->key)) {
            return $this->types['6f7248e3a827b919611e9e32e2542e25'];
        }

        return $this->types[$d->key];
    }
}
