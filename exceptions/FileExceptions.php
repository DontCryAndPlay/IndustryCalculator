<?php
abstract class FileException extends ExceptionBase {}
class FileNotFoundException extends FileException {}
class FileNotReadableException extends FileException {}
class FileAlreadyExistsException extends FileException {}
class FileNotWritableException extends FileException {}
?>