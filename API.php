<?php
/**
 * Copyright (c) 2019 Rajkumar S
 */

require 'simple_html_dom.php';
require 'vendor/autoload.php';

use GuzzleHttp\Client;


class API
{
    private $client;
    private $domain;

    public function __construct()
    {
        $this->domain = "http://172.17.9.22";
        $this->client = new Client(['base_uri' => $this->domain]);
    }

    public function init($move, $query = null, $docType = null, $field = null)
    {
        $response = $this->client->get($this->domain . '/cgi-bin/lsbrows1.cgi?Database_no_opt=++++');
        $html = str_get_html($response->getBody());
        switch ($move) {
            case 'mappings' :
            {
                return $this->getQueryMappings($html);
            }
            case 'search' :
            {
                $form = $html->find('form[name=form_s]');
                $user_name = $form[0]->find('input[name=user_name]')[0]->value;
                return $this->search($form[0]->action, $user_name, $query, $docType, $field);
            }
            case 'checkouts':{
                $form = $html->find('form[action=http://172.17.9.22/cgi-bin/lsbrows4.cgi]');
                $user_name = $form[0]->find('input[name=user_name]')[0]->value;
                return $this->getCheckouts($user_name,$query,$docType);
            }
            default : return [
                'error' => 'Invalid move'
            ];
        }
    }

    private function getQueryMappings($html)
    {
        $docTypes = $html->find('select[name=Docu_type]')[0]->find('option');
        $fields = $html->find('select[name=FIELD]')[0]->find('option');
        $res = [
            'docTypes' => [],
            'fields' => []
        ];
        foreach ($docTypes as $docType) {
            array_push($res['docTypes'],[
                'id' => $docType->value,
                'text' => trim($docType->plaintext)
            ]);
        }
        foreach ($fields as $field) {
            array_push($res['fields'],[
                'id' => $field->value,
                'text' => trim($field->plaintext)
            ]);
        }
        return json_encode($res);

    }

    private function search($action, $user_name, $query, $docType, $field)
    {
        $response = $this->client->request('POST', $action, [
            'form_params' => [
                'user_name' => $user_name,
                'Docu_type' => $docType,
                'FIELD' => $field,
                'T' => $query,
                'OPTION' => '2',
                'ch_period' => '0',
                'TR' => '',
            ],
        ]);
        $html = str_get_html($response->getBody());
        $form = $html->find('form[name=form_disp]')[0];
        $input = $form->find('input[name=user_name]')[0];
        $results = [
            'action' => $form->action,
            'user_name' => $input->value,
            'data' => []
        ];
        foreach ($form->find('a') as $item) {
            array_push($results['data'], [
                'id' => (int)filter_var($item->onclick, FILTER_SANITIZE_NUMBER_INT),
                'title' => html_entity_decode($item->plaintext),
            ]);
        }
        return json_encode($results);
    }

    public function getBookDetails($action, $user_name, $id)
    {
        $response = $this->client->request('POST', $action, [
            'form_params' => [
                'user_name' => $user_name,
                'set_value' => $id,
            ],
        ]);
        $html = str_get_html($response->getBody());
        $form = $html->find('form[name=form_disp]')[0];
        $table = $form->find('table[class=top2wd1]')[0];
        $res = [];
        $currKey = "";
        foreach ($table->find('font') as $item) {

            $temp = $item->plaintext;
            $temp = trim(html_entity_decode($temp), " \t\n\r\0\x0B\xC2\xA0");
            if (($item->color == 'blue' || $item->class == 'fff') && !empty($temp)) {
                $currKey = $temp;
                $currKey = str_replace(":", "", $currKey);
                $currKey = str_replace('\u00a0', "", $currKey);
            } else if ($item->size == 3 && $item->color == 'green') {
                if (!empty($currKey)) {
                    if (array_key_exists($currKey, $res)) {
                        $res[$currKey] = $res[$currKey] . ' ' . $temp;
                    } else {
                        $res[$currKey] = $temp;
                    }
                }
            }
        }
        $res = json_encode($res);
        $res = str_replace('\u00a0', " ", $res);
        $res = json_decode($res, true);
        $a = array_map('trim', array_keys($res));
        $b = array_map('trim', $res);
        $res = array_combine($a, $b);
        return json_encode($res);
    }

    public function getCheckouts($username,$memberid,$password){
        $response = $this->client->request('POST',$this->domain.'/cgi-bin/lsbrows4.cgi',[
            'form_params'=>[
                'user_name'=>$username
            ]
        ]);
        $html = str_get_html($response->getBody());
        $form = $html->find('form[name=barcodecheck]');
        $user_name = $form[0]->find('input[name=user_name]')[0]->value;
        $response = $this->client->request('POST',$form[0]->action,[
            'form_params'=>[
                'user_name'=>$user_name,
                'ID1'=>$memberid,
                'PASS1'=>$password
            ]
        ]);
        $html = str_get_html($response->getBody());
        $name = $html->find('td[width=335]')[0]->plaintext;
        $checkouts = $html->find('td[width=30]')[0]->plaintext;
        $lastChecked = $html->find('td[width=50]')[1]->plaintext;
        $fineDue = $html->find('td[width=45]')[0]->plaintext;
        $form = $html->find('form[action=http://172.17.9.22/cgi-bin/lsbrows4.cgi]');
        $user_name = $form[0]->find('input[name=user_name]')[0]->value;
        $response = $this->client->request('POST',$form[0]->action,[
            'form_params'=>[
                'user_name'=>$user_name
            ]
        ]);
        $html = str_get_html($response->getBody());
        $select = $html->find('select[name="LIST_DISP]');
        $items = [];
        if(sizeof($select) > 0)
            foreach ($select[0]->find('option') as $key=>$item) {
                if(!$key==0){
                    $item = str_replace("&nbsp;"," ",$item->plaintext);
                    array_push($items,$item);
                }
            }
        $output = [
            'name' => trim($name),
            'checkouts'=> trim($checkouts),
            'last_checked'=>trim($lastChecked),
            'fine_due'=>trim($fineDue),
            'items'=>$items
        ];
        return json_encode($output);
    }
}
