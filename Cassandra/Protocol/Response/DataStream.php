<?php
namespace Cassandra\Protocol\Response;

use Cassandra\Enum\DataTypeEnum;

class DataStream {

	/**
	 * @var string
	 */
	private $data;

	/**
	 * @var int
	 */
	private $length;

	/**
	 * @param string $binary
	 */
	public function __construct($binary) {
		$this->data = $binary;
		$this->length = strlen($binary);
	}


	/**
	 * Read data from stream.
	 *
	 * @param int $length
	 * @throws \Exception
	 * @return string
	 */
	protected function read($length) {
		if ($this->length < $length) {
			throw new \Exception('Reading while at end of stream');
		}
		$output = substr($this->data, 0, $length);
		$this->data = substr($this->data, $length);
		$this->length -= $length;
		return $output;
	}

	/**
	 * Read single character.
	 *
	 * @return int
	 */
	public function readChar() {
		return unpack('C', $this->read(1))[1];
	}

	/**
	 * Read unsigned short.
	 *
	 * @return int
	 */
	public function readShort() {
		return unpack('n', $this->read(2))[1];
	}

	/**
	 * Read unsigned int.
	 *
	 * @return int
	 */
	public function readInt($len = 4) {
	  $val = unpack('N', $this->read($len));
		return $val[1];
	}

	public function readIntElement() {
	  $len = $this->readShort();
	  return $this->readint($len);
	}
	
	/**
	 * Read string.
	 *
	 * @return string
	 */
	public function readString() {
		$length = $this->readShort();
	  return $this->read($length);
	}

	/**
	 * Read long string.
	 *
	 * @return string
	 */
	public function readLongString() {
		$length = $this->readInt();
		return $this->read($length);
	}

	/**
	 * Read bytes.
	 *
	 * @return string
	 */
	public function readBytes() {
		$length = $this->readInt();
		return $this->read($length);
	}

	/**
	 * Read uuid.
	 *
	 * @return string
	 */
	public function readUuid() {
		$uuid = '';
		$data = $this->read(16);

		for ($i = 0; $i < 16; ++$i) {
			if ($i == 4 || $i == 6 || $i == 8 || $i == 10) {
				$uuid .= '-';
			}
			$uuid .= str_pad(dechex(ord($data{$i})), 2, '0', STR_PAD_LEFT);
		}

		return $uuid;
	}

	/**
	 * Read timestamp.
	 *
	 * Cassandra is using the default java date representation, which is the
	 * milliseconds since epoch. Since we cannot use 64 bits integers without
	 * extra libraries, we are reading this as two 32 bits numbers and calculate
	 * the seconds since epoch.
	 *
	 * @return int
	 */
	public function readTimestamp() {
		return (integer) round($this->readInt() * 4294967.296 + ($this->readInt() / 1000));
	}

	/**
	 * Read list.
	 *
	 * @param $valueType
	 * @return array
	 */
	public function readList($valueType) {
		$list = array();
		$count = $this->readShort();
		
		echo "readList(0x".dechex($valueType['type']).") elements=$count\n";
		
		file_put_contents("/tmp/x",$this->data);
		
		for ($i = 0; $i < $count; ++$i) {
		  $list[] = $this->readCollectionElementByType($valueType);
		}
		return $list;
	}

	public function readText() {
    return $this->readString();
	}

	
	/**
	 * Read map.
	 *
	 * @param $keyType
	 * @param $valueType
	 * @return array
	 */
	public function readMap($keyType, $valueType) {
		$map = array();
		$count = $this->readShort();

		for ($i = 0; $i < $count; ++$i) {
		  $key = $this->readCollectionElementByType($keyType);
		  $val = $this->readCollectionElementByType($valueType);
		  $map[$key] = $val;
		}
		return $map;
	}

	/**
	 * Read float.
	 *
	 * @return float
	 */
	public function readFloat() {
		return unpack('f', strrev($this->read(4)))[1];
	}

	/**
	 * Read double.
	 *
	 * @return double
	 */
	public function readDouble() {
		return unpack('d', strrev($this->read(8)))[1];
	}

	/**
	 * Read boolean.
	 *
	 * @return bool
	 */
	public function readBoolean() {
	  // null fix
	  if($this->length == 0) {
	    return false;
	  }
	  
		return (bool)$this->readChar();
	}

	/**
	 * Read inet.
	 *
	 * @return string
	 */
	public function readInet() {
		return inet_ntop($this->data);
	}

	/**
	 * Read variable length integer.
	 *
	 * @return string
	 */
	public function readVarint() {
		list($higher, $lower) = array_values(unpack('N2', $this->data));
		return $higher << 32 | $lower;
	}

	/**
	 * Read variable length decimal.
	 *
	 * @return string
	 */
	public function readDecimal() {
		$scale = $this->readInt();
		$value = $this->readVarint();
		$len = strlen($value);
		return substr($value, 0, $len - $scale) . '.' . substr($value, $len - $scale);
	}

	/**
	 * @param array $type
	 * @return mixed
	 */
	public function readByType(array $type) {
	  if($this->length == 0) {
	    return $this->defaultByType($type);
	  }

		switch ($type['type']) {
		  case DataTypeEnum::VARCHAR:
		  case DataTypeEnum::ASCII:
			case DataTypeEnum::TEXT:
				return $this->data;
			case DataTypeEnum::BIGINT:
			case DataTypeEnum::COUNTER:
			case DataTypeEnum::VARINT:
				return $this->readVarint();
			case DataTypeEnum::CUSTOM:
			case DataTypeEnum::BLOB:
				return $this->readBytes();
			case DataTypeEnum::BOOLEAN:
				return $this->readBoolean();
			case DataTypeEnum::DECIMAL:
				return $this->readDecimal();
			case DataTypeEnum::DOUBLE:
				return $this->readDouble();
			case DataTypeEnum::FLOAT:
				return $this->readFloat();
			case DataTypeEnum::INT:
				return $this->readInt();
			case DataTypeEnum::TIMESTAMP:
				return $this->readTimestamp();
			case DataTypeEnum::UUID:
				return $this->readUuid();
			case DataTypeEnum::TIMEUUID:
				return $this->readUuid();
			case DataTypeEnum::INET:
				return $this->readInet();
			case DataTypeEnum::COLLECTION_LIST:
			case DataTypeEnum::COLLECTION_SET:
				return $this->readList($type['value']);
			case DataTypeEnum::COLLECTION_MAP:
				return $this->readMap($type['key'], $type['value']);
		}

		trigger_error('Unknown type ' . var_export($type, true));
		return null;
	}
	
	public function readCollectionElementByType(array $type) {
	  if($this->length == 0) {
	    return $this->defaultByType($type);
	  }

	  switch ($type['type']) {
	    case DataTypeEnum::VARCHAR:
	    case DataTypeEnum::ASCII:
	    case DataTypeEnum::TEXT:
	      return $this->readString();
	    case DataTypeEnum::BIGINT:
	    case DataTypeEnum::COUNTER:
	    case DataTypeEnum::VARINT:
	      return $this->readVarint();
	    case DataTypeEnum::CUSTOM:
	    case DataTypeEnum::BLOB:
	      return $this->readBytes();
	    case DataTypeEnum::BOOLEAN:
	      return $this->readBoolean();
	    case DataTypeEnum::DECIMAL:
	      return $this->readDecimal();
	    case DataTypeEnum::DOUBLE:
	      return $this->readDouble();
	    case DataTypeEnum::FLOAT:
	      return $this->readFloat();
	    case DataTypeEnum::INT:
	      return $this->readInt($this->readShort());
	    case DataTypeEnum::TIMESTAMP:
	      return $this->readTimestamp();
	    case DataTypeEnum::UUID:
	      $this->readShort(); // skip length
	      return $this->readUuid();
	    case DataTypeEnum::TIMEUUID:
	      $this->readShort(); // skip length
	      return $this->readUuid();
	    case DataTypeEnum::INET:
	      return $this->readInet();
	    case DataTypeEnum::COLLECTION_LIST:
	    case DataTypeEnum::COLLECTION_SET:
	      return $this->readList($type['value']);
	    case DataTypeEnum::COLLECTION_MAP:
	      return $this->readMap($type['key'], $type['value']);
	  }
	
	  trigger_error('Unknown type ' . var_export($type, true));
	  return null;
	}
	
	
	/**
	 * TODO: ausdiskitieren
	 * @param array $type
	 * @return string|number|NULL|boolean
	 */
	public function defaultByType(array $type) {
	  switch ($type['type']) {
	    case DataTypeEnum::VARCHAR:
	    case DataTypeEnum::ASCII:
	    case DataTypeEnum::TEXT:
	      return "";
	    case DataTypeEnum::BIGINT:
	    case DataTypeEnum::COUNTER:
	    case DataTypeEnum::VARINT:
	      return 0;
	    case DataTypeEnum::CUSTOM:
	    case DataTypeEnum::BLOB:
	      return null;
	    case DataTypeEnum::BOOLEAN:
	      return false;
	    case DataTypeEnum::DECIMAL:
	      return null;
	    case DataTypeEnum::DOUBLE:
	      return null;
	    case DataTypeEnum::FLOAT:
	      return null;
	    case DataTypeEnum::INT:
	      return null;
	    case DataTypeEnum::TIMESTAMP:
	      return 0;
	    case DataTypeEnum::UUID:
	      return null;
	    case DataTypeEnum::TIMEUUID:
	      return null;
	    case DataTypeEnum::INET:
	      return null;
	    case DataTypeEnum::COLLECTION_LIST:
	    case DataTypeEnum::COLLECTION_SET:
	      return null;
	    case DataTypeEnum::COLLECTION_MAP:
	      return null;
	  }
	
	  trigger_error('Unknown type ' . var_export($type, true));
	  return null;
	}
	
}