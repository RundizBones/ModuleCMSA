<?php


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\Files;


/**
 * Add (+upload) files controller.
 * 
 * @since 0.0.1
 */
class AddController extends \Rdb\Modules\RdbCMSA\Controllers\Admin\RdbCMSAdminBaseController
{


    /**
     * Use `\Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait` to access method that is required for common admin pages.
     */
    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\FilesTrait;


    /**
     * Add (upload) new files.
     * 
     * @return string
     */
    public function doAddAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAFiles', ['add']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf([
            'persistentTokenMode' => true,
        ]);
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        $output['urls'] = $this->getFilesUrlsMethod();

        if (
            isset($_POST[$csrfName]) &&
            isset($_POST[$csrfValue]) &&
            $Csrf->validateToken($_POST[$csrfName], $_POST[$csrfValue])
        ) {
            // if validated token to prevent CSRF.
            unset($_POST[$csrfName], $_POST[$csrfValue]);

            $FileSystem = new \Rdb\Modules\RdbCMSA\Libraries\FileSystem(PUBLIC_PATH);
            $FileSystem->createFolder($this->rootPublicFolderName);

            // prepare data
            $fileFolder = str_replace(['\\', '/', DIRECTORY_SEPARATOR], '/', trim($this->Input->post('filter-file_folder', '')));
            $fileFolder = trim($fileFolder, '/');
            // remove upper path.
            $fileFolder = str_replace(['../', '..\\', '...', '..'], '', $fileFolder);

            $uploadFolder = PUBLIC_PATH . DIRECTORY_SEPARATOR . 
                $this->rootPublicFolderName . DIRECTORY_SEPARATOR .
                str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $fileFolder);

            // validate the form. -----------------------------------------------------------
            $formValidated = false;
            if (empty($_FILES)) {
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'][] = d__('rdbcmsa', 'Please select the file.');
                http_response_code(400);
                $formValidated = false;
            } else {
                $formValidated = true;
            }
            // end validate the form. ------------------------------------------------------

            if ($formValidated === true) {
                // if form validation passed.
                $Upload = new \Rundiz\Upload\Upload('files_inputfiles');
                $FilesSubController = new \Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\FilesSubController();
                $Upload->move_uploaded_to = $uploadFolder;
                $Upload->security_scan = true;
                $Upload->stop_on_failed_upload_multiple = false;
                $uploadResult = $Upload->upload();
                $uploadData = $Upload->getUploadedData();

                $output['uploadFolder'] = $uploadFolder;
                $output['uploadResult'] = $uploadResult;

                if ($uploadResult === true && is_array($uploadData)) {
                    // if upload success (at least one file success).
                    $FilesDb = new \Rdb\Modules\RdbCMSA\Models\FilesDb($this->Container, $this->rootPublicFolderName);
                    $FilesDb->rootPublicFolderName = $this->rootPublicFolderName;
                    $InputUtils = new \Rdb\Modules\RdbCMSA\Libraries\InputUtils();
                    $output['insertedFileIds'] = [];

                    foreach ($uploadData as $key => $item) {
                        if (in_array(strtolower($item['extension']), $FilesSubController->imageExtensions)) {
                            // if the extension is in one of allowed image extensions.
                            // create thumbnail size.
                            $Image = new \Rundiz\Image\Drivers\Gd($item['full_path_new_name']);
                            $Image->jpg_quality = 80;
                            $Image->png_quality = 5;
                            $imageSize = $Image->getImageSize();
                            $thumbnailSizes = $FilesSubController->getThumbnailSizes();
                            foreach ($thumbnailSizes as $name => list($width, $height)) {
                                if ($imageSize['width'] > $width || $imageSize['height'] > $height) {
                                    $saveFile = dirname($item['full_path_new_name']) . DIRECTORY_SEPARATOR . $FileSystem->getSuffixFileName($item['new_name'], '_' . $name);
                                    $Image->resize($width, $height);
                                    $Image->save($saveFile);
                                    $Image->clear();
                                }
                            }// endforeach;
                            unset($height, $name, $width);
                            unset($Image, $imageSize);
                        }

                        $data = [];
                        $data['file_folder'] = $fileFolder;
                        $data['file_visibility'] = 1;
                        $data['file_name'] = $item['new_name'];
                        $data['file_original_name'] = $item['name'];
                        $data['file_mime_type'] = $item['mime'];
                        $data['file_ext'] = $item['extension'];
                        $data['file_size'] = $item['size'];
                        if (stripos($item['mime'], 'image/') !== false) {
                            $data['file_metadata'] = json_encode(
                                [
                                    'image' => $FilesSubController->getImageMetadata($item['full_path_new_name']),
                                ]
                            );
                        } elseif (stripos($item['mime'], 'video/') !== false) {
                            $data['file_metadata'] = json_encode(
                                [
                                    'video' => $FilesSubController->getVideoMetadata($item['full_path_new_name']),
                                    'audio' => $FilesSubController->getAudioMetadata($item['full_path_new_name']),
                                ]
                            );
                        } elseif (stripos($item['mime'], 'audio/') !== false) {
                            $data['file_metadata'] = json_encode(
                                [
                                    'audio' => $FilesSubController->getAudioMetadata($item['full_path_new_name']),
                                ]
                            );
                        }
                        $data['file_media_name'] = trim($this->Input->post('file_media_name', $item['name'], FILTER_SANITIZE_STRING));
                        $data['file_media_description'] = trim($this->Input->post('file_media_description', null));
                        $data['file_media_keywords'] = trim($this->Input->post('file_media_keywords', null, FILTER_SANITIZE_STRING));
                        $data = $InputUtils->setEmptyScalarToNull($data);
                        if (is_null($data['file_folder'])) {
                            // if `file_folder` is null, set to empty string.
                            // this is to prevent the problem when query where `file_folder` = '' but the `file_folder` data is `NULL` and must be query `IS NULL` instead.
                            $data['file_folder'] = '';
                        }
                        $output['insertedFileIds'][] = $FilesDb->add($data);
                        unset($data);
                    }// endforeach;
                    unset($item, $key);

                    unset($FilesDb, $InputUtils);
                }// endif; $uploadResult

                if (is_array($Upload->errorMessagesRaw) && !empty($Upload->errorMessagesRaw)) {
                    // if contain error messages.
                    $output['formResultStatus'] = ($uploadResult === false ? 'error' : 'warning');
                    $output['formResultMessage'] = [];
                    foreach ($Upload->errorMessagesRaw as $errorMessage) {
                        if (isset($errorMessage['message']) && isset($errorMessage['replaces'])) {
                            $output['formResultMessage'][] = vsprintf(__($errorMessage['message']), $errorMessage['replaces']);
                        }
                    }// endforeach;
                    unset($errorMessage);
                    if ($uploadResult === false) {
                        http_response_code(400);
                    }
                }// endif; contain error messages.

                if ($uploadResult === true && empty($Upload->errorMessagesRaw)) {
                    $output['formResultStatus'] = 'success';
                    $output['formResultMessage'] = d__('rdbcmsa', 'Uploaded successfully.');
                    http_response_code(201);
                }

                unset($FilesSubController, $Upload, $uploadData, $uploadResult);
            }

            unset($fileFolder, $FileSystem, $formValidated, $uploadFolder);
        } else {
            // if unable to validate token.
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'] = __('Unable to validate token, please try again. If this problem still occur please reload the page and try again.');
            http_response_code(400);
        }

        unset($csrfName, $csrfValue);
        // generate new token for re-submit the form continueously without reload the page.
        $output = array_merge($output, $Csrf->createToken());

        // display, response part ---------------------------------------------------------------------------------------------
        unset($Csrf, $Url);
        return $this->responseAcceptType($output);
    }// doAddAction


}
