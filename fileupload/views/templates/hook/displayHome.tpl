{* File Upload Form *}
<form action="{$link->getModuleLink('fileupload', 'upload')}" method="post" enctype="multipart/form-data">
    <div class="form-group">
        <label for="upload_file">Choose a file to upload:</label>
        <input type="file" name="upload_file" id="upload_file" class="form-control" required />
    </div>

    <button type="submit" class="btn btn-primary">Upload File</button>
</form>

{if isset($upload_result)}
    <p>{$upload_result}</p>
{/if}