<?php

declare(strict_types=1);

namespace Sandertv\Marshal;

require("DataProcessor.php");
require("DecodeException.php");
require("FileNotFoundException.php");

class Unmarshal{

	/**
	 * json decodes a JSON string into $obj.
	 *
	 * @param string  $json to decode.
	 * @param \object $obj to decode the JSON into.
	 *
	 * @throws DecodeException when $json is invalid JSON data.
	 */
	public static function json(string $json, object $obj) : void{
		$data = json_decode($json, true);
		if($data === null){
			throw new DecodeException("Unmarshal::Json: Invalid JSON string provided");
		}
		$processor = new DataProcessor();
		$processor->put($obj, $data);
	}

	/**
	 * jsonFile gets the content from $file, and decodes JSON data found in the file into $obj.
	 *
	 * @param string  $file to parse JSON from.
	 * @param \object $obj to decode the parsed JSON into.
	 *
	 * @throws DecodeException when the JSON from $file is invalid JSON data.
	 * @throws FileNotFoundException when $file can not be found.
	 */
	public static function jsonFile(string $file, object $obj) : void{
		if(!file_exists($file)){
			throw new FileNotFoundException("Unmarshal::JsonFile: File $file could not be found");
		}
		$fileContent = file_get_contents($file);
		self::json($fileContent, $obj);
	}

	/**
	 * yaml decodes YAML string $yaml into object $obj.
	 *
	 * @param string  $yaml to decode.
	 * @param \object $obj to decode the YAML into.
	 *
	 * @throws DecodeException when $yaml is invalid YAML data.
	 */
	public static function yaml(string $yaml, object $obj) : void{
		$data = yaml_parse($yaml);
		if($data === false){
			throw new DecodeException("Unmarshal::Yaml: Invalid YAML string provided");
		}
		$processor = new DataProcessor();
		$processor->put($obj, $data);
	}

	/**
	 * yamlFile gets the content from $file and decodes the YAML parsed into $obj.
	 *
	 * @param string  $file to parse YAML from.
	 * @param \object $obj to decode the parsed YAML into.
	 *
	 * @throws DecodeException if $file's content was no valid YAML.
	 * @throws FileNotFoundException if $file could not be found.
	 */
	public static function yamlFile(string $file, object $obj) : void{
		if(!file_exists($file)){
			throw new FileNotFoundException("Unmarshal::YamlFile: File $file could not be found");
		}
		$fileContent = file_get_contents($file);
		self::yaml($fileContent, $obj);
	}
}