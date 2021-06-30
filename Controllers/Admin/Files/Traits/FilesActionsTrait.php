<?php


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\Files\Traits;


/**
 * Files actions trait.
 * 
 * @since 0.0.8
 */
trait FilesActionsTrait
{


    use FilesTrait;


    /**
     * Validate file and action.
     * 
     * It's validating file and action must be selected.<br>
     * This method set http response code if contain errors.<br>
     * This method was called from `indexAction()`, `doDeleteAction()` methods.
     * 
     * @param string $file_ids The selected file ID(s).
     * @param string $action The selected action.
     * @return array Return associative array with keys:<br>
     *                          `action` The selected action.<br>
     *                          `actionText` The text of selected action, for displaying.<br>
     *                          `file_ids` The selected file IDs.<br>
     *                          `file_id_array` The selected file IDs as array.<br>
     *                          `formResultStatus` (optional) If contain any error, it also send out http response code.<br>
     *                          `formResultMessage` (optional) If contain any error, it also send out http response code.<br>
     *                          `formValidated` The boolean value of form validation. It will be `true` if form validation passed, and will be `false` if it is not.<br>
     *                          `listSelectedFiles` The selected files. Its structure is `array('total' => x, 'items' => array(...))`.
     */
    protected function validateFileActions(string $file_ids, string $action): array
    {
        $output = [];

        $output['action'] = $action;
        $output['file_ids'] = $file_ids;
        $expFileIds = explode(',', $output['file_ids']);

        if (is_array($expFileIds)) {
            $output['file_id_array'] = $expFileIds;
            $totalSelectedFiles = (int) count($expFileIds);
        } else {
            $output['file_id_array'] = [];
            $totalSelectedFiles = 0;
        }
        unset($expFileIds);

        $formValidated = false;

        // validate selected file and action. ------------------------------
        if ($totalSelectedFiles <= 0) {
            http_response_code(400);
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'][] = d__('rdbcmsa', 'Please select at least one file.');
        } else {
            $formValidated = true;
        }

        if (empty($output['action'])) {
            http_response_code(400);
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'][] = __('Please select an action.');
            $formValidated = false;
        }
        // end validate selected file and action. --------------------------

        // set action text for display.
        if ($output['action'] === 'move') {
            $this->checkPermission('RdbCMSA', 'RdbCMSAFiles', ['move']);
            $output['actionText'] = dn__('rdbcmsa', 'Move file', 'Move files', $totalSelectedFiles);
        } elseif ($output['action'] === 'delete') {
            $this->checkPermission('RdbCMSA', 'RdbCMSAFiles', ['delete']);
            $output['actionText'] = dn__('rdbcmsa', 'Delete file', 'Delete files', $totalSelectedFiles);
        } else {
            $output['actionText'] = $output['action'];
        }

        $FilesDb = new \Rdb\Modules\RdbCMSA\Models\FilesDb($this->Container, $this->rootPublicFolderName);
        // get selected files.
        $options = [];
        $options['file_id_in'] = $output['file_id_array'];
        $output['listSelectedFiles'] = $FilesDb->listItems($options);
        unset($options);
        // populate search count the the posts.
        if (array_key_exists('items', $output['listSelectedFiles']) && is_array($output['listSelectedFiles']['items'])) {
            foreach ($output['listSelectedFiles']['items'] as $row) {
                $file_id = (int) $row->file_id;
                $fileUrl = $row->file_folder . '/' . $row->file_name;
                $row->totalFoundInPosts = $FilesDb->countSearchFileInPosts($fileUrl, $file_id);
            }// endforeach;
            unset($row);
        }
        unset($FilesDb);

        $output['formValidated'] = $formValidated;

        unset($formValidated, $totalSelectedFiles);

        return $output;
    }// validateFileActions


}
