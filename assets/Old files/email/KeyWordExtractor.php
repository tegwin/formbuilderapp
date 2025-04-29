<?php
/**
 * KeywordExtractor Class
 * Extracts keywords from parsed email based on defined rules
 */
class KeywordExtractor {
    private $rules = [];
    
    /**
     * Add a keyword extraction rule
     * 
     * @param string $name Name for the extracted data
     * @param string $pattern Pattern to match
     * @param bool $isRegex Whether pattern is a regex
     * @param string $scope Where to look for the pattern (subject, body, all)
     * @return KeywordExtractor Self for method chaining
     */
    public function addRule($name, $pattern, $isRegex = false, $scope = 'all') {
        if (empty($name) || empty($pattern)) {
            throw new Exception('Rule name and pattern are required');
        }
        
        // Validate regex if applicable
        if ($isRegex) {
            $testResult = @preg_match("/$pattern/", "test");
            if ($testResult === false) {
                throw new Exception('Invalid regular expression pattern');
            }
        }
        
        $this->rules[] = [
            'name' => $name,
            'pattern' => $pattern,
            'is_regex' => $isRegex,
            'scope' => $scope
        ];
        
        return $this;
    }
    
    /**
     * Remove a rule by name
     * 
     * @param string $name Rule name
     * @return KeywordExtractor Self for method chaining
     */
    public function removeRule($name) {
        foreach ($this->rules as $key => $rule) {
            if ($rule['name'] === $name) {
                unset($this->rules[$key]);
                break;
            }
        }
        
        // Re-index array
        $this->rules = array_values($this->rules);
        
        return $this;
    }
    
    /**
     * Clear all rules
     * 
     * @return KeywordExtractor Self for method chaining
     */
    public function clearRules() {
        $this->rules = [];
        return $this;
    }
    
    /**
     * Extract keywords from parsed email
     * 
     * @param array $parsedEmail Parsed email data
     * @return array Extracted keywords
     */
    public function extract($parsedEmail) {
        $extractedKeywords = [];
        
        if (empty($this->rules)) {
            return $extractedKeywords;
        }
        
        foreach ($this->rules as $rule) {
            $searchText = $this->getSearchText($parsedEmail, $rule['scope']);
            $matches = $this->findMatches($searchText, $rule['pattern'], $rule['is_regex']);
            $extractedKeywords[$rule['name']] = $matches;
        }
        
        return $extractedKeywords;
    }
    
    /**
     * Get text to search based on scope
     * 
     * @param array $parsedEmail Parsed email
     * @param string $scope Search scope (subject, body, from, to, all)
     * @return string Text to search
     */
    private function getSearchText($parsedEmail, $scope) {
        $searchText = '';
        
        switch (strtolower($scope)) {
            case 'subject':
                $searchText = isset($parsedEmail['subject']) ? $parsedEmail['subject'] : '';
                break;
                
            case 'body':
            case 'text':
                $searchText = isset($parsedEmail['text']) ? $parsedEmail['text'] : '';
                break;
                
            case 'from':
                if (isset($parsedEmail['from'])) {
                    $searchText = $parsedEmail['from']['name'] . ' ' . $parsedEmail['from']['email'];
                }
                break;
                
            case 'to':
                if (isset($parsedEmail['to']) && is_array($parsedEmail['to'])) {
                    foreach ($parsedEmail['to'] as $to) {
                        $searchText .= $to['name'] . ' ' . $to['email'] . ' ';
                    }
                }
                break;
                
            case 'all':
            default:
                // Combine all searchable fields
                $searchText = isset($parsedEmail['subject']) ? $parsedEmail['subject'] . ' ' : '';
                $searchText .= isset($parsedEmail['text']) ? $parsedEmail['text'] . ' ' : '';
                
                // From address
                if (isset($parsedEmail['from'])) {
                    $searchText .= $parsedEmail['from']['name'] . ' ' . $parsedEmail['from']['email'] . ' ';
                }
                
                // To addresses
                if (isset($parsedEmail['to']) && is_array($parsedEmail['to'])) {
                    foreach ($parsedEmail['to'] as $to) {
                        $searchText .= $to['name'] . ' ' . $to['email'] . ' ';
                    }
                }
                
                // CC addresses
                if (isset($parsedEmail['cc']) && is_array($parsedEmail['cc'])) {
                    foreach ($parsedEmail['cc'] as $cc) {
                        $searchText .= $cc['name'] . ' ' . $cc['email'] . ' ';
                    }
                }
                break;
        }
        
        return $searchText;
    }
    
    /**
     * Find matches in text based on pattern
     * 
     * @param string $text Text to search
     * @param string $pattern Pattern to match
     * @param bool $isRegex Whether pattern is regex
     * @return array Matching strings
     */
    private function findMatches($text, $pattern, $isRegex) {
        $matches = [];
        
        if ($isRegex) {
            // RegEx matching
            preg_match_all("/$pattern/", $text, $regexMatches);
            if (!empty($regexMatches[0])) {
                $matches = $regexMatches[0];
            }
        } else {
            // Exact text matching (case insensitive)
            $patternLower = strtolower($pattern);
            $textLower = strtolower($text);
            
            $pos = strpos($textLower, $patternLower);
            while ($pos !== false) {
                // Get the original case from the text
                $matches[] = substr($text, $pos, strlen($pattern));
                $pos = strpos($textLower, $patternLower, $pos + 1);
            }
        }
        
        return $matches;
    }
}
