<h2>{$file_upload_text}</h2>

{if isset($upload_result)}
    <p>{$upload_result}</p>
{/if}

<!-- File Upload Form -->
<form action="#" method="post" enctype="multipart/form-data">
    <input type="file" name="upload_file" id="upload_file" required />
    <button type="submit" name="submit_fileupload" class="btn btn-primary">Upload File</button>
</form>

<!-- Uploaded Files List -->
{if isset($uploaded_files) && $uploaded_files|@count > 0}
    <h3>Uploaded Files:</h3>
    <form action="#" method="post" enctype="multipart/form-data">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th><input type="checkbox" id="select_all_files" onclick="toggleCheckboxes(this)" /></th>
                    <th>{$l('File Name')}</th>
                    <th>{$l('File Path')}</th>
                    <th>{$l('File Size')}</th>
                    <th>{$l('Date Added')}</th>
                    <th>{$l('Actions')}</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$uploaded_files item=file}
                    <tr>
                        <td><input type="checkbox" name="file_ids[]" value="{$file.id_file}" /></td>
                        <td>{$file.file_name}</td>
                        <td>{$file.file_path}</td>
                        <td>{$file.file_size} bytes</td>
                        <td>{$file.date_add}</td>
                        <td><button type="submit" name="delete_file" value="1" class="btn btn-danger" onclick="deleteFile({$file.id_file})">{$l('Delete')}</button></td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
        <button type="submit" name="delete_multiple_files" class="btn btn-danger">{$l('Delete Selected Files')}</button>
    </form>
{else}
    <p>No files uploaded yet.</p>
{/if}
