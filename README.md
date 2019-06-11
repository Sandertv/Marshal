# Marshal
A PHP library to decode/encode YAML and JSON from a serialised representation directly into a PHP object. It includes type checking and makes sure that the value inserted into class properties is of the correct values.

## Example usage
```php
class Obj {
  /**
  * @marshal c
  */
  public $a = "default value"; // Default field values may be supplied to set the type of a field and the value that is set to the config if it doesn't yet exist.
  public $b = 7;
}

$obj = new Obj();
try{
  Unmarshal::yamlFile("path/to/yaml/file", $obj);
}catch(FileNotFoundException $exception){
  // File doesn't yet exist, marshal the object to the file.
  Marshal::yamlFile("path/to/yaml/file", $obj);
}catch(DecodeException $e){
  echo "corrupted config";
}
```
Usage of the JSON part of the library is identical to the YAML part.
