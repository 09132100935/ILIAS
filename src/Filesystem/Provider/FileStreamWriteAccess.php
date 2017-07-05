<?php

namespace ILIAS\Filesystem\Provider;

use ILIAS\Filesystem\Exception\FileAlreadyExistsException;
use ILIAS\Filesystem\Exception\FileNotFoundException;
use ILIAS\Filesystem\Exception\IOException;
use ILIAS\Filesystem\Stream\FileStream;

/**
 * Interface FileStreamWriteAccess
 *
 * This interface describes the write operations for the streaming filesystem access.
 *
 * @author  Nicolas Schäfli <ns@studer-raimann.ch>
 * @since 5.3
 * @version 1.0
 *
 * @see FileStreamAccess
 *
 * @public
 */
interface FileStreamWriteAccess {

	/**
	 * Writes the stream to a new file.
	 * The directory path to the file will be created.
	 *
	 * The stream will be closed after the write operation is done. Please note that the
	 * resource must be detached from the stream in order to write to the file.
	 *
	 * @param string     $path   The file which should be used to write the stream into.
	 * @param FileStream $stream The stream which should be written into the new file.
	 *
	 * @return void
	 *
	 * @throws IOException                  If the file could not be written to the filesystem.
	 * @throws FileAlreadyExistsException   If the file already exists.
	 *
	 * @since 5.3
	 * @version 1.0
	 *
	 * @see FileStream::detach()
	 */
	public function writeStream($path, FileStream $stream);


	/**
	 * Creates a new file or updates an existing one.
	 * If the file is updated its content will be truncated before writing the stream.
	 *
	 * The stream will be closed after the write operation is done. Please note that the
	 * resource must be detached from the stream in order to write to the file.
	 *
	 * @param string     $path   The file which should be used to write the stream into.
	 * @param FileStream $stream The stream which should be written to the file.
	 *
	 * @return void
	 *
	 * @throws IOException If the stream could not be written to the file.
	 *
	 * @since 5.3
	 * @version 1.0
	 *
	 * @see FileStream::detach()
	 */
	public function putStream($path, FileStream $stream);


	/**
	 * Updates an existing file.
	 * The file content will be truncated to 0.
	 *
	 * The stream will be closed after the write operation is done. Please note that the
	 * resource must be detached from the stream in order to write to the file.
	 *
	 * @param string     $path   The path to the file which should be updated.
	 * @param FileStream $stream The stream which should be used to update the file content.
	 *
	 * @return void
	 *
	 * @throws FileNotFoundException    If the file which should be updated doesn't exist.
	 * @throws IOException              If the file could not be updated.
	 *
	 * @since 5.3
	 * @version 1.0
	 *
	 * @see FileStream::detach()
	 */
	public function updateStream($path, FileStream $stream);
}