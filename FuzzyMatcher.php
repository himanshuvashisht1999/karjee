<?php
class FuzzyMatcher
{
    private $stopWords = []; // Optional stop words

    private $weights = [
        'similar_text' => 0.2,
        'levenshtein' => 0.2,
        'jaccard' => 0.1,
        'phonetic' => 0.1,
        'trigram' => 0.1,
        'weighted_jaccard' => 0.1,
        'cosine' => 0.1,
        'dice' => 0.1
    ];

    private $debug = false;
    private $log = [];

    public function enableDebug($bool = true) {
        $this->debug = $bool;
    }

    private function normalize($str)
    {
        $str = strtoupper($str);
        $str = preg_replace('/[^A-Z0-9 ]+/', '', $str);
        $str = preg_replace('/\s+/', ' ', $str);
        $words = explode(' ', trim($str));
        if (!empty($this->stopWords)) {
            $words = array_diff($words, $this->stopWords);
        }
        return implode(' ', $words);
    }

    private function jaccardSimilarity($a, $b)
    {
        $aWords = array_unique(explode(' ', $a));
        $bWords = array_unique(explode(' ', $b));
        $intersection = array_intersect($aWords, $bWords);
        $union = array_unique(array_merge($aWords, $bWords));
        return count($union) === 0 ? 0 : count($intersection) / count($union);
    }

    private function trigramSimilarity($a, $b)
    {
        $aTrigrams = $this->getNGrams($a, 3);
        $bTrigrams = $this->getNGrams($b, 3);
        $intersection = array_intersect($aTrigrams, $bTrigrams);
        $union = array_unique(array_merge($aTrigrams, $bTrigrams));
        return count($union) === 0 ? 0 : count($intersection) / count($union);
    }

    private function getNGrams($str, $n)
    {
        $str = str_replace(' ', '', $str);
        $ngrams = [];
        for ($i = 0; $i <= strlen($str) - $n; $i++) {
            $ngrams[] = substr($str, $i, $n);
        }
        return $ngrams;
    }

    private function weightedJaccard($a, $b)
    {
        $aTokens = array_count_values(explode(' ', $a));
        $bTokens = array_count_values(explode(' ', $b));
        $intersection = 0;
        $union = 0;

        $allTokens = array_unique(array_merge(array_keys($aTokens), array_keys($bTokens)));
        foreach ($allTokens as $token) {
            $aFreq = $aTokens[$token] ?? 0;
            $bFreq = $bTokens[$token] ?? 0;
            $intersection += min($aFreq, $bFreq);
            $union += max($aFreq, $bFreq);
        }

        return $union === 0 ? 0 : $intersection / $union;
    }

    private function cosineSimilarity($a, $b)
    {
        $aVec = array_count_values(explode(' ', $a));
        $bVec = array_count_values(explode(' ', $b));
        $dot = 0;
        $aMag = 0;
        $bMag = 0;

        $allTokens = array_unique(array_merge(array_keys($aVec), array_keys($bVec)));
        foreach ($allTokens as $token) {
            $aVal = $aVec[$token] ?? 0;
            $bVal = $bVec[$token] ?? 0;
            $dot += $aVal * $bVal;
            $aMag += $aVal ** 2;
            $bMag += $bVal ** 2;
        }

        $denom = sqrt($aMag) * sqrt($bMag);
        return $denom === 0 ? 0 : $dot / $denom;
    }

    private function diceCoefficient($a, $b)
    {
        $aBigrams = $this->getNGrams($a, 2);
        $bBigrams = $this->getNGrams($b, 2);
        $intersection = count(array_intersect($aBigrams, $bBigrams));
        $total = count($aBigrams) + count($bBigrams);
        return $total === 0 ? 0 : (2 * $intersection) / $total;
    }

    public function score($a, $b)
    {
        $aNorm = $this->normalize($a);
        $bNorm = $this->normalize($b);

        similar_text($aNorm, $bNorm, $similarTextScore);
        $levDistance = levenshtein($aNorm, $bNorm);
        $levScore = max(0, 1 - ($levDistance / max(strlen($aNorm), strlen($bNorm))));
        $jaccard = $this->jaccardSimilarity($aNorm, $bNorm);
        $trigram = $this->trigramSimilarity($aNorm, $bNorm);
        $weightedJaccard = $this->weightedJaccard($aNorm, $bNorm);
        $cosine = $this->cosineSimilarity($aNorm, $bNorm);
        $dice = $this->diceCoefficient($aNorm, $bNorm);

        $soundexMatch = soundex($aNorm) === soundex($bNorm);
        $metaphoneMatch = metaphone($aNorm) === metaphone($bNorm);
        $phoneticScore = ($soundexMatch && $metaphoneMatch) ? 1 : (($soundexMatch || $metaphoneMatch) ? 0.5 : 0);

        $score =
            $similarTextScore * $this->weights['similar_text'] +
            $levScore * 100 * $this->weights['levenshtein'] +
            $jaccard * 100 * $this->weights['jaccard'] +
            $phoneticScore * 100 * $this->weights['phonetic'] +
            $trigram * 100 * $this->weights['trigram'] +
            $weightedJaccard * 100 * $this->weights['weighted_jaccard'] +
            $cosine * 100 * $this->weights['cosine'] +
            $dice * 100 * $this->weights['dice'];

        $finalScore = round(min($score, 100), 2);

        $this->log[] = compact(
            'a', 'b', 'aNorm', 'bNorm', 'similarTextScore', 'levScore', 'jaccard', 'trigram',
            'weightedJaccard', 'cosine', 'dice', 'phoneticScore', 'finalScore'
        );

        if ($this->debug) {
            echo "DEBUG: '$aNorm' vs '$bNorm' → $finalScore\n";
        }

        return $finalScore;
    }

    public function level($score)
    {
        if ($score >= 90) return 'High';
        if ($score >= 75) return 'Medium';
        return 'Low';
    }

    public function isMatch($a, $b, $threshold = 85)
    {
        return $this->score($a, $b) >= $threshold;
    }

    public function findBestMatch($input, array $candidates)
    {
        $bestMatch = null;
        $bestScore = 0;

        foreach ($candidates as $candidate) {
            $score = $this->score($input, $candidate);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $candidate;
            }
        }

        return [
            'input' => $input,
            'match' => $bestMatch,
            'score' => $bestScore,
            'level' => $this->level($bestScore)
        ];
    }

    public function suggestAlternatives($input, array $candidates, $topN = 3)
    {
        $scores = [];
        foreach ($candidates as $candidate) {
            $score = $this->score($input, $candidate);
            $scores[] = [
                'match' => $candidate,
                'score' => $score,
                'level' => $this->level($score)
            ];
        }

        usort($scores, fn($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($scores, 0, $topN);
    }

    public function getLog() { return $this->log; }
    public function clearLog() { $this->log = []; }
}
