<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\Settings\CMSAdmin;


/**
 * Settings updater
 * 
 * @since 0.0.6
 */
class UpdaterController extends \Rdb\Modules\RdbCMSA\Controllers\Admin\RdbCMSAdminBaseController
{


    /**
     * Use `\Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait` to access method that is required for common admin pages.
     */
    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\SettingsCMSATrait;


    /**
     * Update settings
     * 
     * @global array $_PATCH
     * @return string
     */
    public function doUpdateAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSASettings', ['update']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf(['persistentTokenMode' => true]);
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $Serializer = new \Rundiz\Serializer\Serializer();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        // make patch data into $_PATCH variable.
        $this->Input->patch('');
        global $_PATCH;

        if (
            isset($_PATCH[$csrfName]) &&
            isset($_PATCH[$csrfValue]) &&
            $Csrf->validateToken($_PATCH[$csrfName], $_PATCH[$csrfValue])
        ) {
            // if validated csrf token passed.
            unset($_PATCH[$csrfName], $_PATCH[$csrfValue]);

            // prepare data for save.
            $data = [];
            $data['rdbcmsa_watermarkAllNewUploaded'] = trim($this->Input->patch('rdbcmsa_watermarkAllNewUploaded'));
            if ($data['rdbcmsa_watermarkAllNewUploaded'] !== '1') {
                $data['rdbcmsa_watermarkAllNewUploaded'] = 0;
            } else {
                $data['rdbcmsa_watermarkAllNewUploaded'] = (int) $data['rdbcmsa_watermarkAllNewUploaded'];
            }

            $ConfigDb = new \Rdb\Modules\RdbAdmin\Models\ConfigDb($this->Container);

            // form validation. ----------------------------------------------------------------------------
            $formValidated = true;
            // end form validation. ------------------------------------------------------------------------

            if (isset($formValidated) && $formValidated === true) {
                // if form validation passed.
                // update to DB.
                try {
                    $updateResult = $ConfigDb->updateMultipleValues($data);

                    if ($this->Input->patch('prog_delete_watermark') === '1') {
                        $default = date('Y-m-dH:i:s').microtime(true).uniqid();
                        $watermark = $this->getWatermarkModuleBasePath() . DIRECTORY_SEPARATOR . $ConfigDb->get('rdbcmsa_watermarkfile', $default);
                        if ($watermark !== $default && !empty($watermark) && is_file($watermark) && is_writable($watermark)) {
                            $deleteResult = @unlink($watermark);
                            if (false === $deleteResult) {
                                throw new \Exception(d__('rdbcmsa', 'Unable to delete watermark file.'));
                            }
                        }
                        if (isset($deleteResult) && true === $deleteResult) {
                            $output['deleteWatermark'] = $ConfigDb->updateValue('', 'rdbcmsa_watermarkfile');
                        } else {
                            $output['deleteWatermark'] = false;
                        }
                        unset($default, $deleteResult, $watermark);
                    }
                } catch (\Exception $e) {
                    $output['exceptionMessage'] = $e->getMessage();
                }

                if (isset($updateResult) && $updateResult === true) {
                    $output['formResultStatus'] = 'success';
                    $output['formResultMessage'] = __('Updated successfully.');
                    http_response_code(200);
                    $output['updateResult'] = $updateResult;
                } else {
                    $output['formResultStatus'] = 'warning';
                    $output['formResultMessage'] = __('Some setting value has not been update, please reload the page and see what was changed.');
                    if (isset($output['exceptionMessage'])) {
                        $output['formResultMessage'] .= '<br>' . PHP_EOL . $output['exceptionMessage'];
                    }
                    http_response_code(400);
                }
            }

            unset($ConfigDb, $data, $formValidated);
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
        unset($Csrf, $Serializer, $Url);
        return $this->responseAcceptType($output);
    }// doUpdateAction


    /**
     * Upload watermark.
     * 
     * @return string
     */
    public function uploadWatermarkAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSASettings', ['update']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $Serializer = new \Rundiz\Serializer\Serializer();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        if (
            isset($_POST[$csrfName]) &&
            isset($_POST[$csrfValue]) &&
            $Csrf->validateToken($_POST[$csrfName], $_POST[$csrfValue])
        ) {
            // if validated csrf token passed.
            unset($_POST[$csrfName], $_POST[$csrfValue]);

            $fileBasePath = $this->getWatermarkModuleBasePath();
            $uploadedWmDir = $this->getWatermarkUploadedDirectory();
            $FileSystem = new \Rdb\Modules\RdbCMSA\Libraries\FileSystem($fileBasePath);
            $FileSystem->createFolder($uploadedWmDir);
            unset($FileSystem);

            if (!is_dir($fileBasePath . DIRECTORY_SEPARATOR . $uploadedWmDir)) {
                throw new \RuntimeException('Unable to create folder ' . $fileBasePath . DIRECTORY_SEPARATOR . $uploadedWmDir . '. Please try to create it manually before continue.');
            }

            $Upload = new \Rundiz\Upload\Upload('rdbcmsa_watermarkfile');
            $Upload->allowed_file_extensions = ['gif', 'png'];
            $Upload->move_uploaded_to = $fileBasePath . DIRECTORY_SEPARATOR . $uploadedWmDir;
            $Upload->new_file_name = 'watermark_' . date('YmdHis');
            $Upload->security_scan = true;
            $Upload->web_safe_file_name = true;
            $uploadResult = $Upload->upload();
            $uploadData = $Upload->getUploadedData();

            if ($uploadResult === true && is_array($uploadData)) {
                // if upload success.
                $uploadedFile = $uploadedWmDir . DIRECTORY_SEPARATOR . $uploadData[0]['new_name'];
                $ConfigDb = new \Rdb\Modules\RdbAdmin\Models\ConfigDb($this->Container);
                $previousWm = $ConfigDb->get('rdbcmsa_watermarkfile');

                if (
                    !empty(trim($previousWm)) && 
                    is_file($fileBasePath . DIRECTORY_SEPARATOR . $previousWm) && 
                    is_writable($fileBasePath . DIRECTORY_SEPARATOR . $previousWm)
                ) {
                    unlink($fileBasePath . DIRECTORY_SEPARATOR . $previousWm);
                }

                $output['uploadResult'] = $uploadResult;
                $output['updateResult'] = $ConfigDb->updateValue($uploadedFile, 'rdbcmsa_watermarkfile');
                $output['uploadedWatermarkPath'] = $fileBasePath . DIRECTORY_SEPARATOR . $uploadedFile;
                unset($ConfigDb, $previousWm);

                $filePath = $output['uploadedWatermarkPath'];
                $filePathExp = explode('/', str_replace(['\\', '/', DIRECTORY_SEPARATOR], '/', $filePath));
                $fileWithExt = $filePathExp[(count($filePathExp) - 1)];
                unset($filePathExp[(count($filePathExp) - 1)]);
                $fileParentDir = implode(DIRECTORY_SEPARATOR, $filePathExp);
                unset($filePathExp);
                $FileSystem = new \Rdb\Modules\RdbCMSA\Libraries\FileSystem($fileParentDir);
                $output['rdbcmsa_watermarkfile_base64'] = $FileSystem->getBase64File($fileWithExt);
                unset($fileParentDir, $filePath, $FileSystem, $fileWithExt);
                unset($uploadedFile);

                $output['formResultStatus'] = 'success';
                $output['formResultMessage'] = d__('rdbcmsa', 'Updated successfully.');
            }

            if (is_array($Upload->errorMessagesRaw) && !empty($Upload->errorMessagesRaw)) {
                // if contain error messages.
                $formValidated = false;
                http_response_code(400);
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'] = [];
                foreach ($Upload->errorMessagesRaw as $errorMessage) {
                    if (isset($errorMessage['message']) && isset($errorMessage['replaces'])) {
                        $output['formResultMessage'][] = vsprintf(__($errorMessage['message']), $errorMessage['replaces']);
                    }
                }// endforeach;
                unset($errorMessage);
            }// endif; contain error messages.

            unset($fileBasePath, $FileSystem, $Upload, $uploadData, $uploadResult, $uploadedWmDir);
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
        unset($Csrf, $Serializer, $Url);
        return $this->responseAcceptType($output);
    }// uploadWatermarkAction


}
