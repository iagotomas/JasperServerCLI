<?php
/**
 * PHP client for JasperServer via SOAP.
 *
 * USAGE:
 * 
 *  $jasper_url = "http://jasper.example.com/jasperserver/services/repository";
 *  $jasper_username = "jasperadmin";
 *  $jasper_password = "topsecret";
 *
 *
 *  $client = new JasperClient($jasper_url, $jasper_username, $jasper_password);
 *
 *  $report_unit = "/my_report";
 *  $report_format = "PDF";
 *  $report_params = array('foo' => 'bar', 'fruit' => 'apple');
 * 
 *  $result = $client->requestReport($report_unit, $report_format,$report_params);
 *
 *  header('Content-type: application/pdf');
 *  echo $result;
 */
class SwASoapClient extends SoapClient
{
    private $_bHandleAsMime = false;
    private $_aAttachments  = array();
    private $_sUploadFile;
 
    public function __doRequest($request, $location, $action, $version, $one_way=0) 
    {
        // Normal operation
        $sResult = parent::__doRequest(
            $request, $location, $action, $version, $one_way);
 
        // Handle and parse MIME-encoded messages
        if($this->_bHandleAsMime) 
        {
            $sResult = $this->_parseMimeMessage($sResult);
            $this->_bHandleAsMime = false;
        }
 
        return $sResult;
    }
    function HttpParseHeaderValue($line) 
    {
    	return $line;
	}
	
	function HttpParseMultipartFile($stream,$name, $boundary, &$array) 
	{
    	$tempdir = sys_get_temp_dir();
	    // we should technically 'clean' name - replace '.' with _, etc
    	// http://stackoverflow.com/questions/68651/get-php-to-stop-replacing-characters-in-get-or-post-arrays


    	$array[$name] = &$fileStruct;

	    if(empty($tempdir)) 
    	{
        	$fileStruct['error'] = UPLOAD_ERR_NO_TMP_DIR;
        	return;
    	}

	    $tempname = tempnam($tempdir, 'php');
    	$outFP = fopen($tempname, 'wb');

	    $fileStruct['tmp_name'] = $tempname;
    	if($outFP === false) 
    	{
	        $fileStruct['error'] = UPLOAD_ERR_CANT_WRITE;
        	return;
    	}

    	$lastLine = null;
	    while(($lineN = fgets($stream, 8096)) !== false && strpos($lineN, $boundary) !== 0) {
	        if($lastLine != null) 
	        {
    	        if(fwrite($outFP, $lastLine) === false) 
    	        {
	                $fileStruct['error'] = UPLOAD_ERR_CANT_WRITE;
                	return;
            	}
        	}
        	$lastLine = $lineN;
    	}

    	if($lastLine != null) 
    	{
	        if(fwrite($outFP, rtrim($lastLine, "\r\n")) === false) 
	        {
                $fileStruct['error'] = UPLOAD_ERR_CANT_WRITE;
                return;
        	}
    	}
	    $fileStruct['error'] = UPLOAD_ERR_OK;
    	$fileStruct['size'] = filesize($tempname);
	}
	function HttpParseMultipartVariable($stream, $boundary, $name, &$array) 
	{
	    $fullValue = '';
    	$lastLine = null;
	    while(($lineN = fgets($stream)) !== false && strpos($lineN, $boundary) !== 0) 
	    {
        	if($lastLine != null) 
        	{
            	$fullValue .= $lastLine;
        	}
	        $lastLine = $lineN;
    	}

	    if($lastLine != null) 
	    {
        	$fullValue .= rtrim($lastLine, "\r\n");
    	}

	    $array[$name] = $fullValue;
	    return $fullValue;
	}
 	private function parseMultipart($data)
 	{
 		$stream = fopen('php://memory','r+');
		fwrite($stream, $data);
		rewind($stream);
    	$partInfo = null;
    	$lineN = fgets($stream);
    	$body = array(); 
    	$data = array();
    	while(($lineN = fgets($stream)) !== false) 
    	{
        	if(strpos($lineN, '--') === 0) 
        	{
            	if(!isset($boundary)) 
            	{
                	$boundary = rtrim($lineN);
            	}
            	continue;
        	}

	        $line = rtrim($lineN);

    	    if($line == '') 
    	    {
			    $body = array();
			    $out = false;
        	    if(strpos($partInfo["Content-Type"],"application")!==FALSE) 
        	    {
                	// parse reamining stream
                	//$line;
                	$this->HttpParseMultipartFile($stream,"temp",$boundary,$body);
                	$out = $body["temp"];
        	    } 
            	else 
            	{
            	    $out = $this->HttpParseMultipartVariable($stream, $boundary, $partInfo["Content-Id"], $body);
            	}
            	$data[]=array(
            		"headers" => $partInfo,
            		"data" => $out);
            	$partInfo = null;
	            continue;
    	    }

        	$delim = strpos($line, ':');

    	    $headerKey = substr($line, 0, $delim);
	        $headerVal = ltrim(substr($line, $delim + 1));
        	$partInfo[$headerKey] = $this->HttpParseHeaderValue($headerVal);
    	}
	    fclose($stream);
	    return $data;
 	}
 	private $parts = null;
    private function _parseMimeMessage($sLastRsp)
    {
    	$this->parts = $this->parseMultipart($sLastRsp);

 		foreach($this->parts as $key=>$data)
 		{
 			if(strpos($data["headers"]["Content-Type"],"text/xml")!==FALSE)
 			{
 				return $data["data"];
 			}
 		}
 		return null;
    }
 	public function hasAttachments()
 	{
		return $this->parts!=null;
 	}
 	public function save($directory)
 	{
 		foreach($this->parts as $key=>$data)
 		{
 			if(strpos($data["headers"]["Content-Type"],"application/octet-stream")!==FALSE)
 			{
 				$this->saveAttachment($data["data"]["tmp_name"],$directory);
 				return ;
 			}
 		}
 	}
 	public function saveAttachment($sFilename, $sDestFile)
 	{
 		copy($sFilename,$sDestFile);
 	}
    public function handleNextRqAsMime()
    {
        $this->_bHandleAsMime = true;
    }
}
class JasperApiClient
{
    private $url;
    private $username;
    private $password;
    private $soapVersion;
    private $style;
    private $use;
    private $client;

    public function __construct($url, $username, $password)
    {
        $this->url = $url;
        $this->username = $username;
        $this->password = $password;
        $this->soapVersion = SOAP_1_2;
        $this->style = SOAP_RPC;
        $this->use = SOAP_LITERAL;
        $this->client = new SwASoapClient(null, array(
            'location'      => $this->url,
            'uri'           => 'urn:',
            'login'         => $this->username,
            'password'      => $this->password,
            'trace'         => 1,
            'exception'     => 1,
            //'proxy_host'	=> "localhost",
            //'proxy_port'	=> 9000,
            'soap_version'  => $this->soapVersion,
            'style'         => $this->style,
            'use'           => $this->use));
        
    }
    protected function callSoap($fn,$request)
    {	
    	try
    	{	
            $result = $this->client->__soapCall($fn, array( new SoapParam($request,"requestXmlString") ));
            return $result;
        }
        catch(SoapFault $exception)
        {
        	//var_dump($client->__getLastRequest());
        	//var_dump($client->__getLastResponse());
        	if($exception->faultcode == "HTTP")
        	{
        		throw new Exception("Check connection to jasperserver webservices [".$exception->faultstring."]!!");
        	}
        	else
        	{
        		throw new Exception("Unexpected error ",0,$exception);
        	}
        }
    
    }
    /**
     * Delete a folder recursively
     * parent parent folder containing the folder to delete
     * folder the folder to delete
     */
    public function deleteFolder($parent,$folder)
    {
    	$request = '<request operationName="delete" locale="en">
						<resourceDescriptor name="'.$folder.'" wsType="folder" uriString="'.$parent.'/'.$folder.'">
							<label>test image</label> 
							<description>test image</description> 
						</resourceDescriptor>
					</request>';
								
    	try
        {
            $result = $this->callSoap('delete',$request);
            return $result;
        }
        catch(SoapFault $exception)
        {
            throw new Exception("Couldn't delete the folder ".$parent."/".$folder,0,$exception);

        }
        return null;
    }
    public function createFolder($parent,$folder,$label,$description)
    {
    	$request = '<request operationName="put" locale="en"> 
    					<resourceDescriptor name="'.$folder.'" wsType="folder" uriString="'.$parent.'/'.$folder.'" isNew="true">
    						<label>'.$label.'</label>
    						<description>'.$description.'</description>
    						<resourceProperty name="PROP_PARENT_FOLDER">
								<value>'.$parent.'</value> 
							</resourceProperty>
						</resourceDescriptor>
					</request>';
					
    	try
        {
            $result = $this->callSoap('put',$request);
            return $result;
        }
        catch(SoapFault $exception)
        {
            throw new Exception("Couldn't create the folder ".$parent."/".$folder,0,$exception);
			
        }
        return null;
    }
    function getResource($uri,$name,$type,$directory)
    {
    	$request = '<request operationName="get" locale="en">
						<resourceDescriptor name="'.$name.'" wsType="'.$type.'" uriString="'.$uri.'" isNew="false">
						</resourceDescriptor>
					</request>';					
        try
        {
            $this->client->handleNextRqAsMime();
            $result = $this->callSoap('get',$request);
            if($this->client->hasAttachments())
            {
            	$this->client->save($directory."/".$name);
            }
            return $result;
        }
        catch(SoapFault $exception)
        {
            throw $exception;
        }
    }
    /**
     * Makes a SOAP call to a Jasper server and returns a ReportList object containing a list of reports available on the server
     *
     * @return the xml response as returned by the service
     */
    public function listFolder($folder)
    {
        $request = '
        <request operationName="list" locale="en">
            <resourceDescriptor name="" wsType="folder" uriString="'.$folder.'" isNew="false">
                <label>null</label>
            </resourceDescriptor>
        </request>';


        try
        {
            $result = $this->callSoap('list',$request);
            return $result;
        }
        catch(SoapFault $exception)
        {
            throw $exception;
        }
        
        return null;
    }

    /**
     * Returns a report that in given $format
     *
     * $report string The name of the report as it appears on the server
     * $format an object able to parse the service response
     * $params an array with key value that will be used as parameters
     */
    public function requestReport($report, $format, $params)
    {
        $params_xml = "";
        foreach ($params as $name => $value)
        {
            $params_xml .= "<parameter name=\"$name\"><![CDATA[$value]]></parameter>\n";
        }

        $request = '
        <request operationName="runReport" locale="en">
          <argument name="RUN_OUTPUT_FORMAT">' . strtoupper(get_class($format)) . '</argument>
          <resourceDescriptor name="" wsType=""
          uriString="' . $report . '"
          isNew="false">
          <label>null</label>
          ' . $params_xml . '
          </resourceDescriptor>
        </request>';

        try
        {
            $result = $this->callSoap('runReport',$request);
            return $result;
        }
        catch(SoapFault $exception)
        {
                throw $exception;
        }
        return null;

    }

}
?>