<?php
namespace exface\Core\Widgets;

use exface\Core\Exceptions\Widgets\WidgetConfigurationError;
use exface\Core\DataTypes\BinaryDataType;
use exface\Core\Widgets\Parts\Uploader;
use exface\Core\CommonLogic\UxonObject;
use exface\Core\DataTypes\StringDataType;

/**
 * Lists files and associated data with optional upload and download.
 * 
 * Technically, this is actually a `DataTable` for files - with built-in
 * upload and download features. 
 * 
 * If each of your data rows represents a file, this widget allows to make 
 * them feel much more like files, than a regular `DataTable` would do: apart
 * from the already mentioned upload and download, you will get file type icons,
 * thumbnails, etc. - depending on the capabilities of the facade, of course.
 * 
 * ## Examples
 * 
 * ### A simple readonly file list
 * 
 * This widget will just show a list of files. The `mime_type_attribute_alias`
 * enables facades to display icons for file types if possible.
 * 
 * ```
 * {
 *   "widget_type": "FileList",
 *   "object_alias": "my.App.FILE_OBJECT",
 *   "filename_attribute_alias": "filename",
 *   "mime_type_attribute_alias": "type"
 * }
 * 
 * ```
 * 
 * ### Upload files
 * 
 * To upload files the `upload_enabled` must be set to `true` and a `file_content_attribute_alias`
 * must be specified (otherwise there's no place to upload). Use `upload_options` to apply
 * restrictions to file size, type, name length, etc.
 * 
 * Very often, files are attached to a master-object (e.g. a customer, and order, etc.).
 * In this case, if the files are kept in a database, you will have a table with file
 * properties and a relation to the master-object. The `FileList` can be used in this
 * case to easily manage attachments if it is placed within a dialog (or similar) showing 
 * the master-object 
 * 
 * ```
 * {
 *   "widget_type": "FileList",
 *   "object_alias": "my.App.FILE_OBJECT",
 *   "filename_attribute_alias": "filename",
 *   "mime_type_attribute_alias": "type",
 *   "file_content_attribute_alias": "inhalt",
 *   "upload_enabled": true,
 *   "upload_options": {
 *     "allowed_mime_types": [
 *       "application/pdf"
 *     ],
 *     "max_file_size_mb": 5
 *   }
 * }
 * 
 * ```
 * 
 * ### Attach files to a master-object
 * 
 * Very often, files are attached to a master-object (e.g. a customer, and order, etc.).
 * In this case, if the files are kept in a database, you will have a table with file
 * properties and a relation to the master-object. The `FileList` can be used in this
 * case to easily manage attachments if it is placed within a dialog (or similar) showing 
 * the master-object as follows:
 * 
 * ```
 * {
 *  "object_alias": "my.App.ORDER",
 *  "widgets": [
 *      {
 *          "attribute_alias": "ORDER_ID",
 *          "id": "order_id_field"
 *      },
 *      {
 *          "widget_type": "FileList",
 *          "object_alias": "my.App.ORDER_ATTACHMENT",
 *          "filename_attribute_alias": "filename",
 *          "mime_type_attribute_alias": "type",
 *          "file_content_attribute_alias": "inhalt",
 *          "upload_enabled": true,
 *          "filters": [
 *              {
 *                  "attribute_alias": "ORDER",
 *                  "required": true,
 *                  "cell_widget": {"widget_type": "InputHidden"}
 *              }
 *          ],
 *          "columns": [
 *              {
 *                  "attribute_alias": "ORDER",
 *                  "value": "=order_id_field",
 *                  "hidden": true
 *              },
 *              {
 *                  "attribute_alias": "UPLOADED_BY"
 *              }
 *          ]
 *      }
 *  ]
 * }
 * 
 * ```
 * 
 * Since the `FileList` is very similar to a `DataTable`, it can also have
 * `filters` and `columns`. The hidden filter over the relation to the master-object
 * makes sure, it list only shows attachments of this one object. The column `ORDER`
 * over that relation ensures, that the upload-data always includes the order id -
 * even for newly added files.
 * 
 * Note, that columns for the `file_name_attribute`, `mime_type_attribute`, etc. are
 * added automatically - we only need to define additional columns. The `ORDER`
 * column is hidden because it's value is the same for all files. The `UPLOADED_BY`
 * column is a typical example for additional information, that can be shown in a
 * file list.
 *
 * @author Andrej Kabachnik
 *        
 */
class FileList extends DataTable
{
    private $filenameAttributeAlias = null;
    
    private $filenameColumn = null;
    
    private $fileContentAttributeAlias = null;
    
    private $fileContentColumn = null;
    
    private $mimeTypeAttributeAlias = null;
    
    private $mimeTypeColumn = null;
    
    private $thumbnailAttributeAlias = null;
    
    private $thumbnailColumn = null;
    
    private $downloadUrlAttributeAlias = null;
    
    private $downloadUrlColumn = null;
    
    private $fileModificationTimeAttributeAlias = null;
    
    private $fileModificationTimeColumn = null;
    
    private $uploader = null;
    
    private $uploaderUxon = null;
    
    private $upload = false;
    
    private $download = false;
    
    /**
     * 
     * @throws WidgetConfigurationError
     * @return DataColumn
     */
    public function getFilenameColumn() : DataColumn
    {
        if ($this->filenameColumn !== null) {
            return $this->filenameColumn;
        }
        throw new WidgetConfigurationError($this, 'No data column with file names found!');
    }
    
    /**
     * The attribute for the file name (including the extension, but without the path)
     * 
     * @uxon-property filename_attribute_alias
     * @uxon-type metamodel:attribute
     * @uxon-required true
     * 
     * @param string $value
     * @return FileList
     */
    public function setFilenameAttributeAlias(string $value) : FileList
    {
        $this->filenameAttributeAlias = $value;
        $col = $this->createColumnFromAttribute($this->getMetaObject()->getAttribute($value), null, true);
        $this->addColumn($col);
        $this->filenameColumn = $col;
        return $this;
    }
    
    /**
     * 
     * @throws WidgetConfigurationError
     * @return DataColumn
     */
    public function getMimeTypeColumn() : DataColumn
    {
        if ($this->mimeTypeColumn !== null) {
            return $this->mimeTypeColumn;
        }
        throw new WidgetConfigurationError($this, 'No data column with mime type found!');
    }
    
    /**
     * The attribute for the mime type - e.g. `application/pdf`, etc.
     *
     * @uxon-property mime_type_attribute_alias
     * @uxon-type metamodel:attribute
     *
     * @param string $value
     * @return FileList
     */
    public function setMimeTypeAttributeAlias(string $value) : FileList
    {
        $this->mimeTypeAttributeAlias = $value;
        $col = $this->createColumnFromAttribute($this->getMetaObject()->getAttribute($value), null, true);
        $this->addColumn($col);
        $this->mimeTypeColumn = $col;
        return $this;
    }
    
    /**
     * 
     * @throws WidgetConfigurationError
     * @return DataColumn
     */
    public function getThumbnailColumn() : DataColumn
    {
        if ($this->thumbnailColumn !== null) {
            return $this->thumbnailColumn;
        }
        throw new WidgetConfigurationError($this, 'No data column with thumbnails found!');
    }
    
    /**
     * 
     * @return bool
     */
    public function hasThumbnailColumn() : bool
    {
        return $this->thumbnailAttributeAlias !== null || $this->thumbnailColumn !== null;
    }
    
    /**
     * The attribute for the thumbnail.
     * 
     * If this property is set, the content of the specified attribute will be used to display
     * a thumbnail for each file - if the facade rendering the `FileList` supports thumbnails,
     * of course.
     *
     * @uxon-property thumbnail_attribute_alias
     * @uxon-type metamodel:attribute
     *
     * @param string $value
     * @return FileList
     */
    public function setThumbnailAttributeAlias(string $value) : FileList
    {
        $this->thumbnailAttributeAlias = $value;
        $col = $this->createColumnFromAttribute($this->getMetaObject()->getAttribute($value), null, true);
        $this->addColumn($col);
        $this->thumbnailColumn = $col;
        return $this;
    }
    
    /**
     * 
     * @throws WidgetConfigurationError
     * @return DataColumn
     */
    public function getFileContentColumn() : DataColumn
    {
        if ($this->fileContentColumn !== null) {
            return $this->fileContentColumn;
        } else {
            foreach ($this->getColumns() as $col) {
                if ($col->getDataType() instanceof BinaryDataType) {
                    $this->fileContentColumn = $col;
                    return $col;
                }
            }
        }
        throw new WidgetConfigurationError($this, 'No data column with file contents found!');
    }
    
    /**
     * The attribute for the file contents (typically a binary).
     * 
     * This property is required if `upload` is enabled.
     *
     * @uxon-property file_content_attribute_alias
     * @uxon-type metamodel:attribute
     *
     * @param string $value
     * @return FileList
     */
    public function setFileContentAttributeAlias(string $value) : FileList
    {
        $this->fileContentAttributeAlias = $value;
        $col = $this->createColumnFromAttribute($this->getMetaObject()->getAttribute($value), null, true);
        $this->addColumn($col);
        $this->fileContentColumn = $col;
        return $this;
    }

    /**
     * 
     * @throws WidgetConfigurationError
     * @return DataColumn
     */
    public function getFileModificationTimeColumn() : DataColumn
    {
        if ($this->fileModificationTimeColumn !== null) {
            return $this->fileModificationTimeColumn;
        }
        throw new WidgetConfigurationError($this, 'No data column with download URLs found!');
    }
    
    /**
     * 
     * @return bool
     */
    public function hasFileModificationTimeColumn() : bool
    {
        return $this->fileModificationTimeAttributeAlias !== null || $this->fileModificationTimeColumn !== null;
    }
    
    /**
     * The attribute for last modification time of the file.
     * 
     * This property is optional, but if the data source supports it, it can be filled automatically
     * when uploading files.
     *
     * @uxon-property file_modification_time_attribute_alias
     * @uxon-type metamodel:attribute
     *
     * @param string $value
     * @return FileList
     */
    public function setFileModificationTimeAttributeAlias(string $value) : FileList
    {
        $this->fileModificationTimeAttributeAlias = $value;
        $col = $this->createColumnFromAttribute($this->getMetaObject()->getAttribute($value), null, true);
        $this->addColumn($col);
        $this->fileModificationTimeColumn = $col;
        return $this;
    }
    
    /**
     * 
     * @throws WidgetConfigurationError
     * @return DataColumn
     */
    public function getDownloadUrlColumn() : DataColumn
    {
        if ($this->downloadUrlColumn !== null) {
            return $this->downloadUrlColumn;
        }
        throw new WidgetConfigurationError($this, 'No data column with download URLs found!');
    }
    
    /**
     * 
     * @return bool
     */
    public function hasDownloadUrlColumn() : bool
    {
        return $this->downloadUrlAttributeAlias !== null || $this->downloadUrlColumn !== null;
    }
    
    /**
     * The attribute for the URL for downloading the file.
     * 
     * This property is needed if the files are stored somewhere, where they can be downloaded from:
     * e.g. in the file system of the workbench server or on a dedicated media-server or dokument
     * management system.
     *
     * @uxon-property download_url_attribute_alias
     * @uxon-type metamodel:attribute
     *
     * @param string $value
     * @return FileList
     */
    public function setDownloadUrlAttributeAlias(string $value) : FileList
    {
        $this->downloadUrlAttributeAlias = $value;
        $col = $this->createColumnFromAttribute($this->getMetaObject()->getAttribute($value), null, true);
        $this->addColumn($col);
        $this->downloadUrlColumn = $col;
        return $this;
    }
    
    /**
     * 
     * @return bool
     */
    public function isUploadEnabled() : bool
    {
        return $this->upload;
    }
    
    /**
     * Set to TRUE to allow uploading files - see `upload_options` for details
     * 
     * @uxon-property upload_enabled
     * @uxon-type boolean
     * @uxon-default false
     * 
     * @param bool $value
     * @return FileList
     */
    public function setUploadEnabled(bool $value) : FileList
    {
        $this->upload = $value;
        return $this;
    }
    
    /**
     * 
     * @return Uploader
     */
    public function getUploader() : Uploader
    {
        if ($this->uploader === null) {
            $uxon = new UxonObject();
            $fileNameType = $this->getFilenameColumn()->getDataType();
            if ($fileNameType instanceof StringDataType && $fileNameType->getLengthMax() !== null) {
                $uxon->setProperty('max_filename_length', $fileNameType->getLengthMax());
            }
            $contentType = $this->getFileContentColumn()->getDataType();
            if ($contentType instanceof BinaryDataType && $contentType->getMaxSizeInMB() !== null) {
                $uxon->setProperty('max_file_size_mb', $contentType->getMaxSizeInMB());
            }
            if ($this->uploaderUxon !== null) {
                $uxon = $uxon->extend($this->uploaderUxon);
            }
            $this->uploader = new Uploader($this, $uxon);
        }
        return $this->uploader;
    }
    
    /**
     * Configuration of file upload restrictions
     * 
     * @uxon-property upload_options
     * @uxon-type \exface\Core\Widgets\Parts\Uploader
     * @uxon-template {"":""}
     * 
     * @param UxonObject $uxon
     * @return FileList
     */
    public function setUploadOptions(UxonObject $uxon) : FileList
    {
        $this->uploaderUxon = $uxon;
        if ($this->uploader !== null) {
            $this->uploader->importUxonObject($uxon);
        }
        return $this;
    }
    
    public function isDownloadEnabled() : bool
    {
        return $this->download;
    }
    
    /**
     * Set to TRUE to allow downloading files
     * 
     * @uxon-property download_enabled
     * @uxon-type boolean
     * @uxon-default false
     * 
     * @param bool $value
     * @return FileList
     */
    public function setDownloadEnabled(bool $value) : FileList
    {
        $this->download = $value;
        return $this;
    }
}