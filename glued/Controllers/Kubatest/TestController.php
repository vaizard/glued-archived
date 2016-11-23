<?php

namespace Glued\Controllers\Kubatest;
use Glued\Controllers\Controller;
use Jsv4\Validator as jsonv;
use Jsv4\SchemaStore;

class TestController extends Controller
{
    public function validationtest($request, $response)
    {
        echo '<h1>test json schema</h1>';
        $schema = file_get_contents('/var/www/html/glued/glued/Controllers/Api/v0_1/schemas/timepixels.json');
        $payload = '
        { "data":
          {
           "titlex": "New event",
           "dt_start": "2017-02-13 15:00",
           "dt_end": "2017-02-13 16:00"
          }
        }
        ';
        
        $payload2 = '
        { "data":
          {
           "title": "New event",
           "dt_start": "2017-02-13 15:00",
           "dt_end": "2017-02-13 16:00"
          }
        }
        ';
        
        //$result = jsonv::validate(json_decode($payload), json_decode($schema));
        
        
        $jsonvr = jsonv::isValid(json_decode($payload), json_decode($schema));
        $jsonvr2 = jsonv::isValid(json_decode($payload2), json_decode($schema));
        //$jsonvr = jsonv::isValid($payload, $schema);
        
        echo '<br /><div>payload je: ('.$payload.')</div>';
        echo '<br /><div>payload2 je: ('.$payload2.')</div>';
        echo '<br /><div>is valid payloadu vraci:</div>';
        var_dump($jsonvr);
        echo '<br /><div>is valid payloadu2 vraci:</div>';
        var_dump($jsonvr2);
        
        
        // pokus cislo 2
        
        echo '<h1>test json schema 2 dle knihovny</h1>';
        /*
        $test_json_schema = '
        {
            "schema": {
                "minLength": 5
            }
        }
        ';
        */
        $test_json_schema = '
        {
            "minLength": 5
        }
        ';
        
        $test_json_data = '
        {
            "data": "tri"
        }
        ';
        
        $test_json_data2 = '
        {
            "data": "je nas tu aspon 5"
        }
        ';
        
        $test_objekt_schema = json_decode($test_json_schema);
        $test_objekt_data = json_decode($test_json_data);
        $test_objekt_data2 = json_decode($test_json_data2);
        
        $validate_result1 = jsonv::isValid($test_objekt_data->data, $test_objekt_schema);
        $validate_result2 = jsonv::isValid($test_objekt_data2->data, $test_objekt_schema);
        
        echo '<div>json schema je: ('.$test_json_schema.')</div>';
        //echo '<div>totez jako objekt:</div>';
        //var_dump($test_objekt_schema);
        
        echo '<div>json data 1 je: ('.$test_json_data.')</div>';
        //echo '<div>totez jako objekt:</div>';
        //var_dump($test_objekt_data);
        
        echo '<div>json data 2 je: ('.$test_json_data2.')</div>';
        
        echo '<br /><div>is valid data 1 vraci:</div>';
        var_dump($validate_result1);
        
        echo '<br /><div>is valid data 2 vraci:</div>';
        var_dump($validate_result2);
        
        //echo '<br /><div>validace vraci:</div>';
        //var_dump($result);
        
        //return $this->container->view->render($response, 'full.twig');
    }
    
    public function schematest($request, $response)
    {
        echo '<h1>test na vlozene schema</h1>';
        
        $schema = file_get_contents('/var/www/html/glued/glued/Controllers/Api/v0_1/schemas/timepixels_root.json');
        
        $payload = '
        { "data":
          {
           "title": "New event",
           "dt_start": "2017-02-13 15:00",
           "dt_end": "2017-02-13 16:00",
           "users": [{
            "id": "2",
            "name": "kuba"
           }]
          }
        }
        ';
        
        $jsonvr = jsonv::isValid(json_decode($payload), json_decode($schema));
        
        echo '<br /><div>payload2 je: ('.$payload.')</div>';
        echo '<br /><div>is valid payloadu vraci:</div>';
        var_dump($jsonvr);
        
        
        /*
        // hlavni schema
        $schema_hlavni = '
            { "$ref": "https://japex.vaizard.xyz/vkladane-schema#/definitions/address" }
        ';
        $schema_hlavni_json = json_decode($schema_hlavni);
        $schema_hlavni_url = 'https://japex.vaizard.xyz/hlavni-schema';
        echo '<div>Hlavni schema</div>';
        echo var_dump($schema_hlavni_json);
        
        // vkladane schema
        $schema_vkladane = '
            {
              "type": "object",
              "properties": {
                "street_address": { "type": "string" },
                "city":           { "type": "string" },
                "state":          { "type": "string" }
              },
              "required": ["street_address", "city", "state"]
            }
        ';
        $schema_vkladane_json = json_decode($schema_vkladane);
        $schema_vkladane_url = 'https://japex.vaizard.xyz/vkladane-schema';
        echo '<div>Vkladane schema</div>';
        echo var_dump($schema_vkladane_json);
        
        // zaciname vkladat
        $store = new SchemaStore();
        
        // vlozime hlavni schema
        $store->add($schema_hlavni_url, $schema_hlavni_json);
        
        // vlozime podradne schema
        $store->add($schema_vkladane_url, $schema_vkladane_json);
        
        $slozene_schema = $store->get($schema_hlavni_url);
        
        echo '<div>Vysledne schema</div>';
        echo var_dump($slozene_schema);
        */
    }
}
