<fieldset>
    <legend><?php echo d__('rdbcmsa', 'Watermark'); ?></legend>
    <div class="form-group">
        <label class="control-label" for="rdbcmsa_watermarkfile"><?php echo d__('rdbcmsa', 'Watermark'); ?></label>
        <div class="control-wrapper">
            <div id="current-watermark-review"></div>
            <div id="rdbcmsa-files-dropzone" class="rdbcmsa-files-dropzone rdbadmin-file-dropzone" title="<?php echo esc_d__('rdbcmsa', 'Drop the files into this area to start upload.'); ?>">
                <span id="rdbcmsa-files-choose-files-button" class="rd-button info rd-inputfile rdbcmsa-button-upload-file" tabindex="0">
                    <span class="label"><i class="fa-solid fa-file-arrow-up"></i> <?php echo d__('rdbcmsa', 'Choose file'); ?></span>
                    <input id="rdbcmsa_watermarkfile" type="file" name="rdbcmsa_watermarkfile" tabindex="-1" accept=".gif,.png">
                </span>
                <span id="rdbcmsa-files-upload-status-placeholder"></span>
                <div id="form-description-in-files-dropzone" class="form-description">
                    <?php echo d__('rdbcmsa', 'Click on choose file or drop a file here to start upload.'); ?> 
                    <?php printf(d__('rdbcmsa', 'Max file size %s.'), ini_get('upload_max_filesize')); ?> 
                </div>
            </div><!--.rdbcmsa-files-dropzone-->
            <label id="prog_delete_watermarkLabel" class="rd-hidden text-color-danger">
                <input id="prog_delete_watermark" type="checkbox" name="prog_delete_watermark" value="1">
                <?php echo d__('rdbcmsa', 'Check this box to delete current watermark.'); ?> 
            </label>
        </div>
    </div>
    <div class="form-group">
        <label class="control-label" for="rdbcmsa_watermarkAllNewUploaded"><?php echo d__('rdbcmsa', 'Apply watermark'); ?></label>
        <div class="control-wrapper">
            <label>
                <input id="rdbcmsa_watermarkAllNewUploaded" type="checkbox" name="rdbcmsa_watermarkAllNewUploaded" value="1">
                <?php echo d__('rdbcmsa', 'Apply watermark on new uploaded'); ?> 
            </label>
            <div class="form-description"><?php echo d__('rdbcmsa', 'If apply, all new uploaded images will be use this watermark otherwise you can manually apply each file.'); ?></div>
        </div>
    </div>
    <div class="form-group">
        <label class="control-label" for="rdbcmsa_watermarkPositionX"><?php echo d__('rdbcmsa', 'Horizontal position'); ?></label>
        <div class="control-wrapper">
            <select id="rdbcmsa_watermarkPositionX" name="rdbcmsa_watermarkPositionX">
                <option value="left"><?php echo d__('rdbcmsa', 'Left'); ?></option>
                <option value="center"><?php echo d__('rdbcmsa', 'Center'); ?></option>
                <option value="right"><?php echo d__('rdbcmsa', 'Right'); ?></option>
            </select>
        </div>
    </div>
    <div class="form-group">
        <label class="control-label" for="rdbcmsa_watermarkPositionY"><?php echo d__('rdbcmsa', 'Vertical position'); ?></label>
        <div class="control-wrapper">
            <select id="rdbcmsa_watermarkPositionY" name="rdbcmsa_watermarkPositionY">
                <option value="top"><?php echo d__('rdbcmsa', 'Top'); ?></option>
                <option value="middle"><?php echo d__('rdbcmsa', 'Middle'); ?></option>
                <option value="bottom"><?php echo d__('rdbcmsa', 'Bottom'); ?></option>
            </select>
        </div>
    </div>
    <div class="form-group">
        <label class="control-label" for="rdbcmsa_watermarkPositionYPadding"><?php echo d__('rdbcmsa', 'Vertical position padding'); ?></label>
        <div class="control-wrapper">
            <input id="rdbcmsa_watermarkPositionYPadding" type="number" name="rdbcmsa_watermarkPositionYPadding" placeholder="20">
        </div>
    </div>
</fieldset>
<div class="form-group">
    <label class="control-label" for="rdbcmsa_imageMaxDimension"><?php echo d__('rdbcmsa', 'Max image dimension'); ?></label>
    <div class="control-wrapper">
        <input id="rdbcmsa_imageMaxDimension" type="text" name="rdbcmsa_imageMaxDimension" placeholder="2000x2000">
        <p class="form-description"><?php printf(d__('rdbcmsa', 'Specify max image dimension in format width%1$sheight.'), '<code>x</code>') ?></p>
    </div>
</div>