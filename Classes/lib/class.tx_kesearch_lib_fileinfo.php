<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Stefan Froemken
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Plugin 'Faceted search - searchbox and filters' for the 'ke_search' extension.
 *
 * @author	Stefan Froemken
 * @package	TYPO3
 * @subpackage	tx_kesearch
 */
class tx_kesearch_lib_fileinfo {

	/**
	 * File
	 *
	 * @var \TYPO3\CMS\Core\Resource\File
	 */
	protected $file = NULL;

	/**
	 * File info
	 *
	 * @var array
	 */
	protected $fileInfo = array();

	/**
	 * set a filename to get informations of
	 *
	 * @param string|\TYPO3\CMS\Core\Resource\File $file
	 * @return boolean is valid file
	 */
	public function setFile($file) {
		return $this->setFileInformations($file);
	}

	/**
	 * collect all fileinformations of given file and
	 * save them to the global fileinformation array
	 *
	 * @param string $file
	 * @return boolean is valid file?
	 */
	protected function setFileInformations($file) {
		$this->fileInfo = array(); // reset previously information to have a cleaned object
		$this->file = $file instanceof \TYPO3\CMS\Core\Resource\File ? $file : NULL;

		if(is_string($file) && !empty($file)) {
			if (TYPO3_VERSION_INTEGER >= 7000000) {
				$this->fileInfo = TYPO3\CMS\Core\Utility\GeneralUtility::split_fileref($file);
			} else {
				$this->fileInfo = t3lib_div::split_fileref($file);
			}
			$this->fileInfo['mtime'] = filemtime($file);
			$this->fileInfo['atime'] = fileatime($file);
			$this->fileInfo['owner'] = fileowner($file);
			$this->fileInfo['group'] = filegroup($file);
			$this->fileInfo['size']  = filesize($file);
			$this->fileInfo['type']  = filetype($file);
			$this->fileInfo['perms'] = fileperms($file);
			$this->fileInfo['is_dir'] = is_dir($file);
			$this->fileInfo['is_file'] = is_file($file);
			$this->fileInfo['is_link'] = is_link($file);
			$this->fileInfo['is_readable'] = is_readable($file);
			$this->fileInfo['is_uploaded'] = is_uploaded_file($file);
			$this->fileInfo['is_writeable'] = is_writeable($file);
		}

		if ($file instanceof \TYPO3\CMS\Core\Resource\File) {
			$pathInfo = \TYPO3\CMS\Core\Utility\PathUtility::pathinfo($file->getName());
			$this->fileInfo = array(
				'file' => $file->getName(),
				'filebody' => $file->getNameWithoutExtension(),
				'fileext' => $file->getExtension(),
				'realFileext' => $pathInfo['extension'],
				'atime' => $file->getCreationTime(),
				'mtime' => $file->getModificationTime(),
				'owner' => '',
				'group' => '',
				'size' => $file->getSize(),
				'type' => 'file',
				'perms' => '',
				'is_dir' => FALSE,
				'is_file' => ($file->getStorage()->getDriverType() === 'Local' ? is_file($file->getForLocalProcessing(FALSE)) : TRUE),
				'is_link' => ($file->getStorage()->getDriverType() === 'Local' ? is_link($file->getForLocalProcessing(FALSE)) : FALSE),
				'is_readable' => TRUE,
				'is_uploaded' => FALSE,
				'is_writeable' => FALSE,
			);
		}
		return $this->fileInfo !== array();
	}

	/**
	 * return relative to site root file path
	 *
	 * @return string Filepath (f.e. fileadmin/user_upload/)
	 */
	public function getRelativePath() {
		if ($this->file !== NULL) {
			return $this->file->getPublicUrl();
		} else {
			return str_replace(PATH_site, '', $this->fileInfo['path']);
		}
	}

	/**
	 * return file path
	 *
	 * @return string Filepath (f.e. /var/www/fileadmin/)
	 */
	public function getPath() {
		if ($this->file !== NULL) {
			return $this->file->getForLocalProcessing(FALSE);
		} else {
			return $this->fileInfo['path'];
		}
	}

	/**
	 * return file name
	 *
	 * @return string Filename (f.e. Bericht von Bernd.PDF)
	 */
	public function getName() {
		return $this->fileInfo['file'];
	}

	/**
	 * return file body
	 *
	 * @return string Filebody (f.e. Bericht von Bernd)
	 */
	public function getBody() {
		return $this->fileInfo['filebody'];
	}

	/**
	 * return file extension
	 *
	 * @return string lowercased Fileextension (f.e. pdf or xml)
	 */
	public function getExtension() {
		return $this->fileInfo['fileext'];
	}

	/**
	 * return the real file extension
	 *
	 * @return string The real file extension (f.e. Xml or PDf or DOC)
	 */
	public function getRealExtension() {
		return $this->fileInfo['realFileext'];
	}

	/**
	 * return files modification time
	 *
	 * @return string Modification time as TimeStamp
	 */
	public function getModificationTime() {
		return $this->fileInfo['mtime'];
	}

	/**
	 * return files last access time
	 *
	 * @return string Last Access time as TimeStamp
	 */
	public function getLastAccessTime() {
		return $this->fileInfo['atime'];
	}

	/**
	 * return files owner
	 *
	 * @return string Owner
	 */
	public function getOwner() {
		return $this->fileInfo['owner'];
	}

	/**
	 * return files group
	 *
	 * @return string Group
	 */
	public function getGroup() {
		return $this->fileInfo['group'];
	}

	/**
	 * return filesize
	 *
	 * @return string Filesize
	 */
	public function getSize() {
		return $this->fileInfo['size'];
	}

	/**
	 * return filetype
	 *
	 * @return string Filetype
	 */
	public function getType() {
		return $this->fileInfo['type'];
	}

	/**
	 * return file permissions
	 *
	 * @return string Filepermissions
	 */
	public function getPermissions() {
		return $this->fileInfo['perms'];
	}

	/**
	 * return if file is a directory
	 *
	 * @return boolean is file a directory
	 */
	public function getIsDirectory() {
		return $this->fileInfo['is_dir'];
	}

	/**
	 * return if file is a file
	 *
	 * @return boolean is file a file
	 */
	public function getIsFile() {
		return $this->fileInfo['is_file'];
	}

	/**
	 * return if file is a (sym)link
	 *
	 * @return boolean is file a (sym)link
	 */
	public function getIsSymLink() {
		return $this->fileInfo['is_link'];
	}

	/**
	 * return if file is readable
	 *
	 * @return boolean is file readable
	 */
	public function getIsReadable() {
		return $this->fileInfo['is_readable'];
	}

	/**
	 * return if file is uploaded
	 *
	 * @return boolean is file an uploaded file from POST/GET
	 */
	public function getIsUploaded() {
		return $this->fileInfo['is_uploaded'];
	}

	/**
	 * return if file is writable
	 *
	 * @return boolean is file writeable
	 */
	public function getIsWriteable() {
		return $this->fileInfo['is_writeable'];
	}

	/**
	 * return all fileinformations
	 *
	 * @return array all file informations
	 */
	public function getAllFileInformations() {
		return $this->fileInfo;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ke_search/Classes/lib/class.tx_kesearch_lib_fileinfo.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ke_search/Classes/lib/class.tx_kesearch_lib_fileinfo.php']);
}
?>
