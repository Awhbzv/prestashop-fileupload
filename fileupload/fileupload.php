Here is your updated code with the suggested changes applied:

```php
<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class Fileupload extends Module
{
    public function __construct()
    {
        $this->name = 'fileupload';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'awhbzv';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        parent::__construct();
        $this->displayName = $this->l('File Upload');
        $this->description = $this->l('A simple file upload form for the back office configuration.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module?');
    }

    public function install()
    {
        if (!parent::install() || !$this->registerHook('displayBackOfficeHeader')) {
            return false;
        }

        // Create the database table for storing file information
        return $this->createDatabaseTable();
    }

    private function createDatabaseTable()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'fileupload_files` (
            `id_file` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `file_name` VARCHAR(255) NOT NULL,
            `file_path` VARCHAR(255) NOT NULL,
            `file_size` INT NOT NULL,
            `date_add` DATETIME NOT NULL,
            PRIMARY KEY (`id_file`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        return Db::getInstance()->execute($sql);
    }

    public function getContent()
    {
        $output = '';

        // Handle file upload when the form is submitted
        if (Tools::isSubmit('submit_fileupload')) {
            $output .= $this->processFileUpload();
        }

        // Handle file deletion
        if (Tools::isSubmit('delete_file')) {
            $output .= $this->processFileDeletion();
        }

        // Handle multiple file deletions
        if (Tools::isSubmit('delete_multiple_files')) {
            $output .= $this->processMultipleFileDeletion();
        }

        // Fetch uploaded files from the database
        $uploaded_files = $this->getUploadedFiles();

        // Assign the uploaded files to smarty
        $this->context->smarty->assign([
            'uploaded_files' => $uploaded_files,
        ]);

        return $output . $this->renderForm();
    }

    public function renderForm()
    {
        $form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('File Upload Configuration'),
                    'icon' => 'icon-cogs'
                ],
                'input' => [
                    [
                        'type' => 'file',
                        'label' => $this->l('Choose File to Upload'),
                        'name' => 'upload_file',
                        'desc' => $this->l('Please choose a file to upload.')
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Upload File'),
                    'name' => 'submit_fileupload',
                    'class' => 'btn btn-primary'
                ]
            ]
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->fields_value['upload_file'] = '';
        $helper->submit_action = 'submit_fileupload';

        return $helper->generateForm([$form]) . $this->displayUploadedFiles();
    }

    public function processFileUpload()
    {
        if (!isset($_FILES['upload_file']) || $_FILES['upload_file']['error'] !== UPLOAD_ERR_OK) {
            return $this->displayError('There was an error uploading your file.');
        }

        $allowed_extensions = ['jpg', 'png', 'pdf'];
        $file_extension = pathinfo($_FILES['upload_file']['name'], PATHINFO_EXTENSION);

        if (!in_array(strtolower($file_extension), $allowed_extensions)) {
            return $this->displayError('Invalid file type. Allowed types: jpg, png, pdf.');
        }

        $upload_dir = _PS_MODULE_DIR_ . 'fileupload/uploads/';

        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $upload_path = $upload_dir . basename($_FILES['upload_file']['name']);

        if (move_uploaded_file($_FILES['upload_file']['tmp_name'], $upload_path)) {
            $this->saveFileToDatabase($_FILES['upload_file']['name'], $upload_path, $_FILES['upload_file']['size']);
            return $this->displayConfirmation('File uploaded and saved to database successfully!');
        } else {
            return $this->displayError('Failed to move uploaded file.');
        }
    }

    private function saveFileToDatabase($file_name, $file_path, $file_size)
    {
        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'fileupload_files` (`file_name`, `file_path`, `file_size`, `date_add`) 
                VALUES ("' . pSQL($file_name) . '", "' . pSQL($file_path) . '", ' . (int)$file_size . ', NOW())';

        Db::getInstance()->execute($sql);
    }

    private function getUploadedFiles()
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'fileupload_files` ORDER BY `date_add` DESC';
        return Db::getInstance()->executeS($sql);
    }

    private function processFileDeletion()
    {
        $file_id = Tools::getValue('file_id');

        if (empty($file_id)) {
            return $this->displayError('No file selected for deletion.');
        }

        $file = $this->getFileDetails($file_id);

        if (!$file) {
            return $this->displayError('File not found.');
        }

        if (file_exists($file['file_path'])) {
            unlink($file['file_path']);
        }

        Db::getInstance()->delete('fileupload_files', 'id_file = ' . (int)$file_id);

        return $this->displayConfirmation('File deleted successfully!');
    }

    private function processMultipleFileDeletion()
    {
        $file_ids = Tools::getValue('file_ids');

        if (empty($file_ids) || !is_array($file_ids)) {
            return $this->displayError('No files selected for deletion.');
        }

        foreach ($file_ids as $file_id) {
            $file = $this->getFileDetails($file_id);

            if ($file && file_exists($file['file_path'])) {
                unlink($file['file_path']);
                Db::getInstance()->delete('fileupload_files', 'id_file = ' . (int)$file_id);
            }
        }

        return $this->displayConfirmation('Selected files deleted successfully!');
    }

    private function getFileDetails($file_id)
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'fileupload_files` WHERE `id_file` = ' . (int)$file_id;
        return Db::getInstance()->getRow($sql);
    }

    private function displayUploadedFiles()
    {
        $uploaded_files = $this->getUploadedFiles();

        if (empty($uploaded_files)) {
            return '<p>No files uploaded yet.</p>';
        }

        $html = '<h3>Uploaded Files:</h3>';
        $html .= '<form action="' . $_SERVER['REQUEST_URI'] . '" method="POST">';
        $html .= '<table class="table table-bordered">';
        $html .= '<thead>
                    <tr>
                        <th><input type="checkbox" id="select_all_files" onclick="toggleCheckboxes(this)" /></th>
                        <th>' . $this->l('File Name') . '</th>
                        <th>' . $this->l('File Path') . '</th>
                        <th>' . $this->l('File Size') . '</th>
                        <th>' . $this->l('Date Added') . '</th>
                        <th>' . $this->l('Actions') . '</th>
                    </tr>
                </thead>';
        $html .= '<tbody>';

        foreach ($uploaded_files as $file) {
            $html .= '<tr>
                        <td><input type="checkbox" name="file_ids[]" value="' . $file['id_file'] . '" class="file_checkbox" /></td>
                        <td>' . $file['file_name'] . '</td>
                        <td>' . $file['file_path'] . '</td>
                        <td>' . $file['file_size'] . ' bytes</td>
                        <td>' . $file['date_add'] . '</td>
                        <td>
                            <button type="submit" name="delete_file" value="1" class="btn btn-danger" onclick="return confirm(\'Are you sure you want to delete this file?\')">Delete</button>
                            <input type="hidden" name="file_id" value="' . $file['id_file'] . '" />
                        </td>
                    </tr>';
        }

        $html .= '</tbody></table>';
        $html .= '<button type="submit" name="delete_multiple_files" value="1" class="btn btn-danger">Delete Selected Files</button>';
        $html .= '</form>';

        $html .= '<script>
                    function toggleCheckboxes(source) {
                        var checkboxes = document.querySelectorAll(".file_checkbox");
                                               for (var i = 0; i < checkboxes.length; i++) {
                            checkboxes[i].checked = source.checked;
                        }
                    }
                  </script>';

        return $html;
    }
}
