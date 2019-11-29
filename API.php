<?php

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

    public function fireUp($query)
    {
        $response = $this->client->get($this->domain . '/cgi-bin/lsbrows1.cgi?Database_no_opt=++++');
        $html = str_get_html($response->getBody());
        $form = $html->find('form[name=form_s]');
        $user_name = $form[0]->find('input[name=user_name]')[0]->value;
        return $this->search($form[0]->action, $user_name, $query);
    }

    private function search($action, $user_name, $query)
    {
        $response = $this->client->request('POST', $action, [
            'form_params' => [
                'user_name' => $user_name,
                'Docu_type' => '0',
                'FIELD' => '3',
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
        ];
        foreach ($form->find('a') as $item) {
            array_push($results, [
                'id' => (int) filter_var($item->onclick, FILTER_SANITIZE_NUMBER_INT),
                'title' => html_entity_decode($item->plaintext),
            ]);
        }
        return $results;
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
                        $res[$currKey] = $res[$currKey] . ', ' . $temp;
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
}
