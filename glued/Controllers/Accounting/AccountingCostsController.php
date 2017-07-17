<?php
namespace Glued\Controllers\Accounting;

use Glued\Controllers\Controller;

class AccountingCostsController extends Controller
{
    
    // shows basic page with all costs
    public function getCosts($request, $response)
    {
        $costs_output = '';
        $this->container->db->orderBy("c_uid","asc");
        $bills = $this->container->db->get('accounting_accepted');
        if (count($bills) > 0) {
            foreach ($bills as $data) {
                $billdata = json_decode($data['c_data'], true);
                $costs_output .= '<div id="cost_row_'.$data['c_uid'].'">ID: '.$data['c_uid'].', bill number: '.$billdata['bill-nr'].' <a href="'.$this->container->router->pathFor('accounting.editcostform').$data['c_uid'].'">edit</a> | <span style="cursor: pointer; color: red;" onclick="delete_cost('.$data['c_uid'].');">delete</span></div>';
            }
        }
        else {
            $costs_output = 'zatim zadne nejsou vlozeny';
        }
        
        $additional_javascript = '
    <script>
    function delete_cost(cost_id) {
        if (confirm("do you really want to delete this cost?")) {
            $.ajax({
              url: "https://'.$this->container['settings']['glued']['hostname'].$this->container->router->pathFor('accounting.api.delete').'" + cost_id,
              dataType: "text",
              type: "DELETE",
              data: "voiddata=1",
              success: function(data) {
                $("#cost_row_" + cost_id).remove();
              },
              error: function(xhr, status, err) {
                alert("ERROR: xhr status: " + xhr.status + ", status: " + status + ", err: " + err);
              }
            });
        }
    }
    </script>
        ';
        
        return $this->container->view->render($response, 'accounting/costs.twig', array('costs_output' => $costs_output, 'additional_javascript' => $additional_javascript));
    }
    
    // show form for add new cost
    public function addCostForm($request, $response)
    {
        $form_output = 'zde je formular';
        $json_uischema_output = '
{
    "date-plneni": {
      "ui:widget": "alt-date"
    },
    "poznamka": {
      "ui:widget": "textarea"
    },
    "prirazeni": {
        "items": {
          "poznamka": {
            "ui:widget": "textarea"
          }
        }
    }
}
        ';
        //     "required" : [ "wovat", "vat" ],
        
        $json_schema_output = '
{
    "title": "New bill form",
    "type": "object",
    "properties":{
        "files": {
          "type": "array",
          "title": "Multiple files",
          "items": {
            "type": "string",
            "format": "data-url"
          }
        },
        "dodavatel": {
            "title": "Dodavatel",
            "type": "object",
            "properties": {
                "nazev": {
                    "type": "string",
                    "title": "Název",
                },
                "ico": {
                    "type": "string",
                    "title": "IČ",
                },
                "adresa": {
                    "type": "string",
                    "title": "Adresa",
                }
            }
        },
        "date-plneni": {
          "type": "string",
          "format": "date",
          "title": "Datum zdanitelného plnění",
        },
        "bill-nr": {
          "type": "string",
          "title": "Číslo účtenky"
        },
        "generated-nr": {
          "type": "string",
          "title": "Generované číslo"
        },
        "prirazeni": {
          "type": "array",
          "title": "Přiřazení k zakázkám",
          "items": {
            "type": "object",
            "properties": {
                "zakazka-nr": {
                    "type": "string",
                    "title": "Číslo zakázky",
                },
                "sum": {
                    "type": "string",
                    "title": "Částka k zakázce",
                },
                "poznamka": {
                    "type": "string",
                    "title": "Poznámka k zakázce",
                }
            }
          }
        },
        "poznamka": {
          "type": "string",
          "title": "Poznámka"
        },
        "wovat": {
          "type": "string",
          "title": "Cena bez DPH"
        },
        "vat": {
          "type": "string",
          "title": "Cena s DPH"
        }
        
    }
}
        ';
        
        // zakladni data, jedna prazdna polozka arraye "prirazeni"
        $json_formdata_output = '
{
  "prirazeni": [
    {
      "zakazka-nr": "",
      "sum": "",
      "poznamka": ""
    }
  ]
}
        ';
        //$this->container['settings']['glued']['hostname']
        //$this->container->settings->glued->hostname
        // vnitrek onsubmit funkce
        //         alert('xhr status: ' + xhr.status + ', status: ' + status + ', err: ' + err)
        $json_onsubmit_output = '
    $.ajax({
      url: "https://'.$this->container['settings']['glued']['hostname'].$this->container->router->pathFor('accounting.api.new').'",
      dataType: "text",
      type: "POST",
      data: "billdata=" + JSON.stringify(formData.formData),
      success: function(data) {
        
        ReactDOM.render((<div><h1>Thank you</h1><pre>{JSON.stringify(formData.formData, null, 2) }</pre></div>), 
                 document.getElementById("main"));
        
      },
      error: function(xhr, status, err) {
        
        ReactDOM.render((<div><h1>Something goes wrong ! not saving.</h1><pre>{JSON.stringify(formData.formData, null, 2) }</pre></div>), 
                 document.getElementById("main"));
      }
    });
        ';
        
        return $this->container->view->render($response, 'accounting/addcost.twig', array(
            'form_output' => $form_output,
            'json_schema_output' => $json_schema_output,
            'json_uischema_output' => $json_uischema_output,
            'json_formdata_output' => $json_formdata_output,
            'json_onsubmit_output' => $json_onsubmit_output
        ));
    }
    
    // show form for edit existing cost
    public function editCostForm($request, $response, $args)
    {
        
        $this->container->db->where("c_uid", $args['id']);
        $data = $this->container->db->getOne('accounting_accepted');
        
        
        $form_output = 'zde je formular';
        $json_uischema_output = '
{
    "date-plneni": {
      "ui:widget": "alt-date"
    },
    "poznamka": {
      "ui:widget": "textarea"
    },
    "prirazeni": {
        "items": {
          "poznamka": {
            "ui:widget": "textarea"
          }
        }
    }
}
        ';
        //     "required" : [ "wovat", "vat" ],
        
        $json_schema_output = '
{
    "title": "Edit bill form",
    "type": "object",
    "properties":{
        "files": {
          "type": "array",
          "title": "Multiple files",
          "items": {
            "type": "string",
            "format": "data-url"
          }
        },
        "dodavatel": {
            "title": "Dodavatel",
            "type": "object",
            "properties": {
                "nazev": {
                    "type": "string",
                    "title": "Název",
                },
                "ico": {
                    "type": "string",
                    "title": "IČ",
                },
                "adresa": {
                    "type": "string",
                    "title": "Adresa",
                }
            }
        },
        "date-plneni": {
          "type": "string",
          "format": "date",
          "title": "Datum zdanitelného plnění",
        },
        "bill-nr": {
          "type": "string",
          "title": "Číslo účtenky"
        },
        "generated-nr": {
          "type": "string",
          "title": "Generované číslo"
        },
        "prirazeni": {
          "type": "array",
          "title": "Přiřazení k zakázkám",
          "items": {
            "type": "object",
            "properties": {
                "zakazka-nr": {
                    "type": "string",
                    "title": "Číslo zakázky",
                },
                "sum": {
                    "type": "string",
                    "title": "Částka k zakázce",
                },
                "poznamka": {
                    "type": "string",
                    "title": "Poznámka k zakázce",
                }
            }
          }
        },
        "poznamka": {
          "type": "string",
          "title": "Poznámka"
        },
        "wovat": {
          "type": "string",
          "title": "Cena bez DPH"
        },
        "vat": {
          "type": "string",
          "title": "Cena s DPH"
        }
        
    }
}
        ';
        
        // zakladni data, jedna prazdna polozka arraye "prirazeni"
        $json_formdata_output = $data['c_data'];
        
        // vnitrek onsubmit funkce
        //         alert('xhr status: ' + xhr.status + ', status: ' + status + ', err: ' + err)
        $json_onsubmit_output = '
    $.ajax({
      url: "https://'.$this->container['settings']['glued']['hostname'].$this->container->router->pathFor('accounting.api.edit').$args['id'].'",
      dataType: "text",
      type: "PUT",
      data: "billdata=" + JSON.stringify(formData.formData),
      success: function(data) {
        
        ReactDOM.render((<div><h1>Record was updated succesfully</h1><pre>{JSON.stringify(formData.formData, null, 2) }</pre></div>), 
                 document.getElementById("main"));
        
      },
      error: function(xhr, status, err) {
        
        ReactDOM.render((<div><h1>Something goes wrong ! not saving.</h1><pre>{JSON.stringify(formData.formData, null, 2) }</pre></div>), 
                 document.getElementById("main"));
      }
    });
        ';
        
        
        return $this->container->view->render($response, 'accounting/editcost.twig', array(
            'form_output' => $form_output,
            'json_schema_output' => $json_schema_output,
            'json_uischema_output' => $json_uischema_output,
            'json_formdata_output' => $json_formdata_output,
            'json_onsubmit_output' => $json_onsubmit_output,
            'cost_id' => $args['id']
        ));
    }
    
}
