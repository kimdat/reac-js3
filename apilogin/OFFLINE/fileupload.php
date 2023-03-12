<?php

namespace Offline;

use Error;
use Exception;
use Throwable;

$path = 'uploads/';
class fileupload
{
  function fileUpload()
  {
    try {
      if (isset($_FILES['files'])) {
        $files = $_FILES['files'];
        $pathFile = [];
        $filesCount = count($files['name']);
        for ($i = 0; $i < $filesCount; $i++) {
          try {
            if (array_key_exists('name', $files) && array_key_exists($i, $files['name']) && array_key_exists('tmp_name', $files) && array_key_exists($i, $files['tmp_name'])) {
              $file_name = $files['name'][$i];
              $file_tmp = $files['tmp_name'][$i];
              $pathFile[] = self::saveFile($file_name, $file_tmp, $i);
            }
          } catch (\Throwable $th) {
            //throw $th;
          }
        }
        return json_encode($pathFile);
      }
    } catch (Throwable $e) {
      $currentFile = basename(__FILE__);
      throw new Error("Error in $currentFile ->" . $e->getMessage());
    }
  }

  function saveFile($file_name, $file_tmp, $indexFile)
  {
    try {
      global $path;
      $upload_dir = $path . date('Y/m/d') . '/';
      if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
      }
      $filename = time() . '_' . substr(md5(rand()), 0, 6);
      $destination = $upload_dir . $filename . '.' . pathinfo($file_name, PATHINFO_EXTENSION);

      if (move_uploaded_file($file_tmp, $destination)) {
        // File uploaded successfully
        return array("path" => $destination, "indexPath" => $indexFile);
      } else {
        // Error uploading file
        return array("Error" => "Error when move_upload_file at " . $indexFile, "indexPath" => $indexFile);
      }
    } catch (Throwable $e) {
      return array("Error" => "Error when save file at " . $indexFile . " ." . $e->getMessage(), "indexPath" => $indexFile);
    }
  }
}
