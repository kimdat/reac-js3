<?php

require __DIR__ . '/vendor/autoload.php';

use Slim\Psr7\Response;
use Slim\Psr7\Request;
use Slim\Factory\AppFactory;

try {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: *');
    require_once 'bootstrap.php';
    require_once 'config.php';
    require_once 'classOnline.php';
    require_once 'classOffline.php';
    require_once 'DeviceStatus.php';
    require_once 'middleware.php';

    $devicesDefine = new DevicesOnline();
    $inventoriesDefine = new InventoriesOnline();
    $currentURL =  $_SERVER['REQUEST_URI'];
    $currentURL = basename($currentURL);
    $url_not_requied = ["fileupload", "executeOnline", "exportFileExcelSSH"];
    if (!in_array($currentURL, $url_not_requied)) {
        require_once 'DbConnect.php';
    }
    if (isset($_SERVER['HTTP_FLAGOFFLINE'])) {
        $devicesDefine = new DevicesOffline();
        $inventoriesDefine = new InventoriesOffline();
    }
    $app = AppFactory::create();
    $app->setBasePath(BASE_PATH);
    $app->addBodyParsingMiddleware();
    function writeSucces($data)
    {
        $response = new Response();
        $response->getBody()->write($data);
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
    }
    function writeErr($e)
    {
        $response = new Response();
        $responseErr = array("error" => $e->getMessage());
        $response->getBody()->write(json_encode($responseErr));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
    function writeBadRequest(Response $response)
    {
        return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    }

    //offline
    $app->post('/login', function (Request $request, Response $response, array $args) use ($app) {
        try {
            $login = new Offline\login;
            return writeSucces(json_encode($login->login()));
        } catch (Error $e) {
            return writeErr($e);
        }
    })->add('checkToken');

    //online
    $app->post('/createOnline', function (Request $request, Response $response, $args) {

        try {
            $createOnline = new Online\createOnline;
            return writeSucces($createOnline->createOnline());
        } catch (Error $e) {
            return writeErr($e);
        }
    })->add('checkToken');
    //get taất cả devices
    $app->get('/devices', function (Request $request, Response $response, array $args) use ($app) {
        try {

            $devices = new ManageInventories\devices;
            return writeSucces($devices->getAllDevices());
        } catch (Error $e) {
            return writeErr($e);
        }
    })->add('checkToken');
    $app->get('/childDevice', function (Request $request, Response $response, array $args) use ($app) {
        try {

            $childDevice = new  ManageInventories\childDevice;
            return writeSucces($childDevice->getChildDevice());
        } catch (Error $e) {
            return writeErr($e);
        }
    })->add('checkToken');
    $app->post('/expandAll', function (Request $request, Response $response, array $args) use ($app) {
        try {

            $getExpandAll = new  ManageInventories\getexpandall;
            return writeSucces($getExpandAll->getExpandAll());
        } catch (Error $e) {
            return writeErr($e);
        }
    })->add('checkToken');
    $app->get('/deleteRow', function (Request $request, Response $response, array $args) use ($app) {
        try {

            $deleteRow = new ManageInventories\deleteRow;
            return writeSucces($deleteRow->deleteRow());
        } catch (Error $e) {
            return writeErr($e);
        }
    })->add('checkToken');
    $app->get('/filterData', function (Request $request, Response $response, array $args) use ($app) {
        try {

            $filterData = new ManageInventories\filterData;
            return writeSucces($filterData->filterData());
        } catch (Error $e) {
            return writeErr($e);
        }
    })->add('checkToken');
    $app->post('/exportFileExcel', function (Request $request, Response $response, array $args) use ($app) {
        try {
            $downloadFileExcel = new ManageInventories\exportFileExcel();
            return $downloadFileExcel->downExcel();
        } catch (Error $e) {
            $response = new Response();
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    })->add('checkToken');
    $app->post('/getDataTrung', function (Request $request, Response $response, array $args) use ($app) {
        try {
            $getDataTrung = new Offline\getDataTrung();
            return writeSucces($getDataTrung->getDataTrung());
        } catch (Error $e) {
            return writeErr($e);
        }
    })->add('checkToken');
    $app->post('/fileupload', function (Request $request, Response $response, array $args) use ($app) {
        try {
            $fileupload = new Offline\fileupload();

            return writeSucces($fileupload->fileUpload());
        } catch (Error $e) {
            return writeErr($e);
        }
    })->add('checkToken');

    $app->post('/insertDataUpload', function (Request $request, Response $response, array $args) use ($app) {
        try {
            $insertDataUpload = new Offline\insertDataUpload();
            return writeSucces($insertDataUpload->insertDataUpload());
        } catch (Error $e) {
            return writeErr($e);
        }
    })->add('checkToken');

    $app->get('/devicesOnline', function (Request $request, Response $response, array $args) use ($app) {
        try {
            $devicesOnline = new Online\getAllDevices();
            return writeSucces($devicesOnline->getAllDevices());
        } catch (Error $e) {
            return writeErr($e);
        }
    })->add('checkToken');
    $app->post('/executeOnline', function (Request $request, Response $response, array $args) use ($app) {
        try {
            $executeOnline = new Online\ExecuteConnectDevice();
            return writeSucces($executeOnline->ExecuteConnectDevice());
        } catch (Error $e) {
            return writeErr($e);
        }
    })->add('checkToken');
    $app->post('/exportFileExcelSSH', function (Request $request, Response $response, array $args) use ($app) {
        try {
            $downloadFileExcel = new Online\exportFileExcelSSH();
            return $downloadFileExcel->downExcel();
        } catch (Error $e) {
            $response = new Response();
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    })->add('checkToken');
    $app->post('/checkDuplicate', function (Request $request, Response $response, array $args) use ($app) {
        try {
            $checkDuplicate = new Online\CheckDuplicate();
            return writeSucces($checkDuplicate->CheckDuplicate());
        } catch (Error $e) {
            return writeErr($e);
        }
    });
    // ->add('checkToken');

    $app->get('/api/devices', function (Request $request, Response $response, array $args) {
        try {
            $filters = $request->getQueryParams();
            if ($filters) {
                return writeSucces(Online\Device::getFilteredDevices($filters));
            } else {
                return writeSucces(Online\Device::getAllDevices());
            }
        } catch (Error $e) {
            return writeErr($e);
        }
    });

    $app->get('/api/device-status', function (Request $request, Response $response, array $args) {
        try {
            return writeSucces(Online\Device::getAllDeviceStatus());
        } catch (Error $e) {
            return writeErr($e);
        }
    });

    $app->get('/api/provinces', function (Request $request, Response $response, array $args) {
        $filters = $request->getQueryParams();
        try {
            if (isset($filters['region_id'])) {
                $regionId = $filters['region_id'];
                return writeSucces(Online\Province::getProvincesByRegionId($regionId));
            } else {
                return writeSucces(Online\Province::getAllProvinces());
            }
        } catch (Error $e) {
            return writeErr($e);
        }
    });

    $app->get('/api/regions', function (Request $request, Response $response, array $args) {
        try {
            return writeSucces(Online\Region::getAllRegions());
        } catch (Error $e) {
            return writeErr($e);
        }
    });

    $app->post('/api/devices/delete', function (Request $request, Response $response, array $args) use ($app) {
        try {
            return writeSucces(Online\Device::deleteDevices($request->getParsedBody()["list"]));
        } catch (Error $e) {
            return writeErr($e);
        }
    });

    $app->post('/api/devices/{id}', function (Request $request, Response $response, array $args) {
        $id = $args['id'];
        try {
            $result = Online\Device::modifyDevice($id, $request->getParsedBody());
            if ($result == true) {
                return writeSucces(json_encode([
                    'message' => "This device has been updated successfully."
                ]));
            } else {
                $response->getBody()->write(json_encode([
                    "message" => "There was an error updating the device."
                ]));
                return writeBadRequest($response);
            }
        } catch (Error $e) {
            return writeErr($e);
        }
    });

    $app->post('/updateInventories', function (Request $request, Response $response, array $args) use ($app) {
        try {
            $instantaneouscheck = new Online\INSTANTANEOUSCHECK();
            return writeSucces($instantaneouscheck->updateInventories());
        } catch (Error $e) {
            return writeErr($e);
        }
    })->add('checkToken');

    $app->post('/api/devices', function (Request $request, Response $response, array $args) {
        try {
            return writeSucces(Online\Device::addDevice($request->getParsedBody()));
        } catch (Error $e) {
            return writeErr($e);
        }
    });



    $app->get('/api/devices/export', function (Request $request, Response $response, array $args) {
        try {
            Online\Device::export($response);
            return $response;
        } catch (Error $e) {
            return writeErr($e);
        }
    });
    $app->post('/api/connectDevices', function (Request $request, Response $response, array $args) {
        try {
            return writeSucces(Online\Device::connectDevice($request->getParsedBody()));
        } catch (Error $e) {
            return writeErr($e);
        }
    });

    $app->get('/api/device-types', function (Request $request, Response $response, array $args) {
        try {
            return writeSucces(Online\Device::getAllDeviceTypes());
        } catch (Error $e) {
            return writeErr($e);
        }
    });

    $app->run();
} catch (Error $e) {
    throw new Error($e->getMessage());
}
