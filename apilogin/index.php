<?php

require __DIR__ . '/vendor/autoload.php';








use Slim\Psr7\Response;
use Slim\Psr7\Request;
use Slim\Factory\AppFactory;

try {

    include 'DbConnect.php';
    include 'config.php';
    include 'OFFILE/getAllId.php';
    include 'OFFILE/exportFileExcel.php';
    include 'OFFILE/login.php';
    include 'middleware.php';
    include 'OFFILE/insertDataUpload.php';
    include 'OFFILE/devices.php';
    include 'OFFILE/childDevice.php';
    include 'OFFILE/getexpandall.php';
    include 'OFFILE/filterData.php';
    include 'OFFILE/deleteRow.php';
    include 'OFFILE/getDataTrung.php';
    include 'OFFILE/fileupload.php';
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: *');
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
    $app->post('/login', function (Request $request, Response $response, array $args) use ($app) {
        try {

            $loginResponse = login();
            return writeSucces(json_encode($loginResponse));
        } catch (Error $e) {
            return writeErr($e);
        }
    })->add('checkToken');


    $app->get('/devices', function (Request $request, Response $response, array $args) use ($app) {
        try {

            $devices = getAllDevices();
            return writeSucces($devices);
        } catch (Error $e) {
            return writeErr($e);
        }
    })->add('checkToken');
    $app->get('/childDevice', function (Request $request, Response $response, array $args) use ($app) {
        try {

            $childDevice = getChildDevice();
            return writeSucces($childDevice);
        } catch (Error $e) {
            return writeErr($e);
        }
    })->add('checkToken');
    $app->post('/expandAll', function (Request $request, Response $response, array $args) use ($app) {
        try {

            $getExpandAll = getExpandAll();
            return writeSucces($getExpandAll);
        } catch (Error $e) {
            return writeErr($e);
        }
    })->add('checkToken');
    $app->get('/deleteRow', function (Request $request, Response $response, array $args) use ($app) {
        try {

            $deleteRow = deleteRow();
            return writeSucces($deleteRow);
        } catch (Error $e) {
            return writeErr($e);
        }
    })->add('checkToken');
    $app->get('/filterData', function (Request $request, Response $response, array $args) use ($app) {
        try {

            $filterData = filterData();
            return writeSucces($filterData);
        } catch (Error $e) {
            return writeErr($e);
        }
    })->add('checkToken');
    $app->post('/exportFileExcel', function (Request $request, Response $response, array $args) use ($app) {
        try {
            $downloadFileExcel = downExcel();
            return $downloadFileExcel;
        } catch (Error $e) {
            $response = new Response();
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    })->add('checkToken');
    $app->post('/getDataTrung', function (Request $request, Response $response, array $args) use ($app) {
        try {
            $getDataTrung = getDataTrung();
            return writeSucces($getDataTrung);
        } catch (Error $e) {
            return writeErr($e);
        }
    })->add('checkToken');
    $app->post('/fileupload', function (Request $request, Response $response, array $args) use ($app) {
        try {
            $fileupload = fileupload();
            return writeSucces($fileupload);
        } catch (Error $e) {
            return writeErr($e);
        }
    })->add('checkToken');

    $app->post('/insertDataUpload', function (Request $request, Response $response, array $args) use ($app) {
        try {
            $insertDataUpload = insertDataUpload();
            return writeSucces($insertDataUpload);
        } catch (Error $e) {
            return writeErr($e);
        }
    })->add('checkToken');

    $app->get('/getAllId', function (Request $request, Response $response, array $args) use ($app) {
        try {
            $getAllId = getAllId();
            return writeSucces($getAllId);
        } catch (Error $e) {
            return writeErr($e);
        }
    })->add('checkToken');
    $app->run();
} catch (Error $e) {
    throw new Error($e->getMessage());
}
