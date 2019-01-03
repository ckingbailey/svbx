<?php
use codeguy\Upload;
use codeguy\Upload\Exception;

include_once('error_handling/sqlErrors.php');

/* takes params:
**  $link => a joshcam\MysqliDb object
**  $key => the key of the files in $_FILES
**  $dir => the destination directory
**  $assocID => the ID of the relevant Deficiency
*/
function uploadAttachment($link, $key, $dir, int $assocID) {
    $userID = intval($_SESSION['userID']);
    $filetypes = explode(',',
        preg_replace('/\s+/', '', file_get_contents('allowedFormats.csv')));
    $fullDir = $_SERVER['DOCUMENT_ROOT'] . '/' . $dir;
    $storage = new \Upload\Storage\FileSystem($fullDir);
    $attachment = new \Upload\File($key, $storage);
    $filename = $attachment->getNameWithExtension();
    $filename = addslashes($filename);
    $filepath = $dir . '/' . $filename;
    $filesize = $attachment->getSize();
    $fileext = $attachment->getExtension();
    $attachment->addValidations($filetypes);
    // $sql = 'INSERT bartdlAttachments (bdaFilepath, bartdlID, uploaded_by, filesize, fileext, filename) VALUES (?, ?, ?, ?, ?, ?)';
    $types = 'siiiss';
    
    try { // upload file
        $attachment->upload();
    } catch (UploadException $e) {
        throw $e;
    }
    
    try { // commit file data to db
        // if (!$stmt = $link->prepare($sql)) throw new mysqli_sql_exception($link->error);
        // if (!$stmt->bind_param($types,
        $fields = [
            'bdaFilepath' => $filepath,
            'bartdlID' => $assocID,
            'uploaded_by' => $userID,
            'filesize' => $filesize,
            'fileext' => $fileext,
            'filename' => $filename
        ];
        // )) throw new mysqli_sql_exception($stmt->error);
        // if (!$stmt->execute()) throw new mysqli_sql_exception($stmt->error . " : userID = $userID");
        // $stmt->close();
        if ($link->insert('bartdlAttachments', $fields)) {
            return $filepath;
        } else throw new Exception ('There was an error uploading the attachment, ' . $link->getLastError());
    } catch (\mysqli_sql_exception $e) {
        throw $e;
    } catch (\Exception $e) {
        throw $e;
    } catch (\Error $e) {
        throw $e;
    } finally {
        if (!empty($link) && is_a($link, 'MysqliDb')) $link->disconnect();
    }
}