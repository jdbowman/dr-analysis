<?php

class drSurveyMonkey {
	
    // ----------------
    // Member variables
    // ----------------
    
    private $m_api_key;
    private $m_auth_token;
    private $m_surveys_url;
    private $m_translate_dictionary;


    // ------------------------
    // Constructor / destructor
    // ------------------------
    
    function __construct($config) {

        // Store the SurveyMonkey configuration
        $this->m_api_key = $config["sm_api_key"];
        $this->m_auth_token = $config["sm_auth_token"];
        $this->m_surveys_url = $config["sm_surveys_url"];
        $this->m_translate_dictionary = NULL;
    }


    // ---------------------------
    // Helper functions
    // ---------------------------

    public function setTranslateDictionary($dictionary) {
        $this->m_translate_dictionary = $dictionary;
    }

    protected function translate($original) {
        if (!is_null($this->m_translate_dictionary)) {
            if (isset($this->m_translate_dictionary[$original])) {
                return $this->m_translate_dictionary[$original];
            }
        }

        return $original;
    }

    protected function getAuthContext() {
       return( stream_context_create( array( 'http' => array( 'header' => "Authorization:bearer " . $this->m_auth_token ) ) ) );
    }

    protected function appendApiKey($url) {

        // If api_key isn't already in the URL, then add it
        if (is_null(parse_str(parse_url($url)["query"])["api_key"])) {
            return $this->appendToUrl($url, "api_key", $this->m_api_key);
        } else {
            return $url;
        }
    }

    protected function appendToUrl($url, $key, $value) {

        $query = parse_url($url, PHP_URL_QUERY);
        if ($query) {
            $token = "&";
        } else {
            $token = "?";
        }

        return ($url . $token . $key . "=" . $value);
    }

    protected function getShortName($question_id, $element_id) {
        if (is_null($element_id))
            $short = "col_" . $question_id;
        else
            $short = "col_" . $question_id . "_" . $element_id;

        return $this->translate($short);
    }

    protected function getLongName($question_text, $element_text) {
        if (is_null($element_text))
            return $question_text;
        else
            return $question_text . " | " . $element_text; 
    }

    protected function addElementRow(&$table, $question_id, $question_family, $element_id, $element_text, $element_category, $element_position) {

        $table->addRow( [
            "question_id"=>$question_id, 
            "question_family"=>$question_family, 
            "element_id"=>$element_id, 
            "element_text"=>$element_text, 
            "element_category"=>$element_category, 
            "element_position"=>$element_position, 
            "response_column"=>$this->getShortName($question_id, $element_id) ] );
    }

    protected function addResponseColumn(&$table, $question_id, $question_text, $question_family, $element_id, $element_text) {

        $column = new drTableColumn();
        $column->setName($this->getShortName($question_id, $element_id));
        $column->setDescription($this->getLongName($question_text, $element_text));

        if ($question_family=="datetime") {
            $column->setDatatype("TIME");
        }
        
        $table->addColumn($column);
    }

    protected function insertEntry(&$row, $key, $value) {
        $row[$key] = $this->translate($value);
        return TRUE;
    }


    // ---------------------------
    // Public functions
    // ---------------------------

    // ---------------------------------------
    // Fetch list of surveys from SurveyMonkey
    // ---------------------------------------
	function fetchSurveys() {

        $url = $this->appendApiKey($this->m_surveys_url);
        $json = file_get_contents($url, false, $this->getAuthContext());
        $data = json_decode($json, true);

        return($data);
	}


    // -------------------------------------
    // Fetch survey detail from SurveyMonkey
    // -------------------------------------
    function fetchSurveyDetail($survey_id) {

        $url = $this->appendApiKey($this->m_surveys_url . $survey_id . "/details");
        $json = file_get_contents($url, false, $this->getAuthContext());
        $data = json_decode($json, true);

        return($data);
    }

    // ----------------------------------------------------
    // Get URL to access survey responess from SurveyMonkey
    // ----------------------------------------------------
    function getResponsesUrl($survey_id, $per_page = NULL) {
        if (is_null($per_page)) {
            $per_page = 90;
        }
        return ($this->appendToUrl($this->appendApiKey($this->m_surveys_url . $survey_id . "/responses/bulk"), "per_page", $per_page));
    }


    // ----------------------------------------
    // Fetch survey responses from SurveyMonkey
    // ----------------------------------------
    function fetchSurveyResponses($survey_id, $url=NULL) {

        if (is_null($url)) {
            $url = $this->getResponsesUrl($survey_id);
        }      

        $json = file_get_contents($this->appendApiKey($url), false, $this->getAuthContext()); 
        $data = json_decode($json, true);

        return($data);
    }

    // -------------------
    // Parse survey detail
    // -------------------
    function parseSurveyDetail($data) {
       
        // Allocate table definitions for responses and elements
        $responses_table = new drTable();
        $elements_table = new drTable();
        $elements_table->addColumn(new drTableColumn("question_id"));
        $elements_table->addColumn(new drTableColumn("question_family"));
        $elements_table->addColumn(new drTableColumn("element_id"));
        $elements_table->addColumn(new drTableColumn("element_text"));
        $elements_table->addColumn(new drTableColumn("element_category"));
        $elements_table->addColumn(new drTableColumn("element_position"));
        $elements_table->addColumn(new drTableColumn("response_column"));

        // Add standard columns to response table definition
        $this->addResponseColumn($responses_table, "response_id", "Response ID", NULL, NULL, NULL);
        $this->addResponseColumn($responses_table, "response_status", "Response status", NULL, NULL, NULL);

        // Loop over survey detail page to generate columns and labels
        foreach ($data["pages"] as $p) {

            $questions = $p["questions"];

            foreach ($questions as $q) {

                $question_id = $q["id"];
                $question_text = strip_tags($q["headings"][0]["heading"]);
                $question_family = strip_tags($q["family"]);

                $this->addElementRow($elements_table, $question_id, $question_family, NULL, $question_text, "questions", NULL);

                if (array_key_exists("answers", $q)) {
                
                    $answers = $q["answers"];

                    //print_r($answers);

                    // Check if answers is an array
                    if (is_array($answers)) {

                        // Check if the array contains id and text fields
                        if (array_key_exists("id", $answers) and array_key_exists("text", $answers)) {

                            $element_id = strip_tags($answers["id"]);
                            $element_text = strip_tags($answers["text"]);
                            $element_position = strip_tags($answers["position"]);

                            // Add to column and label lists
                            $this->addResponseColumn($responses_table, $question_id, $question_text, $question_family, NULL, NULL);
                            $this->addElementRow($elements_table, $question_id, $question_family, $element_id, $element_text, "answers", $element_position);
                        } 

                        // Add sub-elements of questions
                        $subs = ["rows", "other"];
                        $bool_subs = [];
                        $bool_subs_total = FALSE;

                        foreach ($subs as $s) {

                            $bool_subs[$s] = FALSE;

                            // If there is a subarray, then loop over it and add each part
                            if (array_key_exists($s, $answers)) {

                                $bool_subs[$s] = TRUE;
                                $bool_subs_total = TRUE;

                                // Add each sub part as an element
                                if (drUtility::countDim($answers[$s]) > 1) {

                                    foreach ($answers[$s] as $r) {
                         
                                        $element_id = strip_tags($r["id"]);
                                        $element_text = strip_tags($r["text"]);
                                        $element_position = strip_tags($r["position"]);

                                        // Add to column and label lists
                                        $this->addResponseColumn($responses_table, $question_id, $question_text, $question_family, $element_id, $element_text);
                                        $this->addElementRow($elements_table, $question_id, $question_family, $element_id, $element_text, $s, $element_position);              
                                    } 

                                } else {

                                    $r = $answers[$s];
                                    $element_id = strip_tags($r["id"]);
                                    $element_text = strip_tags($r["text"]);
                                    $element_position = strip_tags($r["position"]);

                                    // Add to column and label lists
                                    $this->addResponseColumn($responses_table, $question_id, $question_text, $question_family, $element_id, $element_text);
                                    $this->addElementRow($elements_table, $question_id, $question_family, $element_id, $element_text, $s, $element_position);              
                                }
                            } 
                        }          

                        // There is a "choices" subarray
                        $bool_choices = FALSE;
                        if (array_key_exists("choices", $answers)) {

                            $bool_choices = TRUE;

                            foreach ($answers["choices"] as $c) {

                                $element_id = strip_tags($c["id"]);
                                $element_text = strip_tags($c["text"]);
                                $element_position = strip_tags($c["position"]);

                                // Add to label list only
                                $this->addElementRow($elements_table, $question_id, $question_family, $element_id, $element_text, "choices", $element_position);
                            }
                        }

                        // No subarrays, so add the question itself
                        if (!$bool_subs_total or ($bool_choices and !$bool_subs["rows"])) {

                            // Add question to columns list (already in labels)
                            $this->addResponseColumn($responses_table, $question_id, $question_text, $question_family, NULL, NULL);
                        }

                    // Answers is not an array - the question has no sub-elements
                    } else {

                        // Add question to columns list (already in labels)
                        $this->addResponseColumn($responses_table, $question_id, $question_text, $question_family, NULL, NULL);
                    }

                // Answers does not exist - the question has no sub-elements
                } else {

                    // Add question to columns list (already in labels)
                    $this->addResponseColumn($responses_table, $question_id, $question_text, $question_family, NULL, NULL);
                }
            }
        }

        return ["responses_table"=>$responses_table, "elements_table"=>$elements_table];
    }
        

    // ----------------------
    // Parse survey resopnses
    // ----------------------
    function parseSurveyResponses($data, &$responses)
    {
        // See if there are more pages to process after the current one
        $next_url = NULL;
        $links = $data["links"];
        if (array_key_exists("next", $links)) {
            $next_url = $links["next"];
        }

        // Find out some basic info about the responses
        $total = $data["total"];
        echo "Total responses for survey: " . $total . "\n";
        $per_page = $data["per_page"];
        echo "Responses per page: " . $per_page . "\n";
        $page = $data["page"];
        echo "Current page: " . $page . "\n";
        echo "Processed so far: " . count($responses) . "\n";

        // Loop over the responses
        foreach ($data["data"] as $d) {

            $response_id = $d["id"];
            $response_status = $d["response_status"];

            if (!$response_status == "completed") {
                echo "INCOMPLETE RESPONSE skipped -- id: " . $response_id . ", status: " . $response_status ."\n";
                //print_r($d);
                //echo "\n";
            } else {

                // Create a new empty row
                $row = [];

                // Store the response id and status
                $this->insertEntry($row, $this->getShortName("response_id", NULL), $response_id);
                $this->insertEntry($row, $this->getShortName("response_status", NULL), $response_status);

                // Loop over all questions recorded in the response and add them to the row
                foreach ($d["pages"] as $p) {

                    foreach ($p["questions"] as $q) {

                        $question_id = $q["id"];
                        $answers = $q["answers"];

                        // Loop over all answers and store them
                        foreach ($answers as $a) {

                            $element_id = NULL;
                            $element_value = NULL;

                            if (array_key_exists("row_id", $a)) {
                                $element_id = $a["row_id"];
                            } else if (array_key_exists("other_id", $a)) {
                                $element_id = $a["other_id"];
                            }

                            if (array_key_exists("text", $a)) {
                                $element_value = $a["text"];
                            } else if (array_key_exists("choice_id", $a)) {
                                $element_value = $a["choice_id"]; 
                            } else if (array_key_exists("other_id", $a)) {
                                $element_value = $a["other_id"];
                            }

                            // Insert entry into row
                            $out = $this->insertEntry($row, $this->getShortName($question_id, $element_id), $element_value);
                        }
                    }
                }
            }

            // Add row to full response array
            array_push($responses, $row);
        }

        return $next_url;
    }


} // end class


?>


