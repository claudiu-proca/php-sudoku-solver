<?php

echo PHP_EOL;

if (empty($argv[1])) {
	$file = __DIR__ . '/input-1.txt';
} else {
	$file = __DIR__ . '/' . $argv[1];
}

if (!file_exists($file)) {
	echo 'Input file "' . basename($file) . '" not found!' . PHP_EOL;
	exit();
}

$input = file_get_contents($file);

if (!valid($input)) exit();

const MAX_RUNS = 30;
const SEE_ALL = false;
const GROUPS = [
    '11-33' => ['1-1', '1-2', '1-3', '2-1', '2-2', '2-3', '3-1', '3-2', '3-3'],
    '14-36' => ['1-4', '1-5', '1-6', '2-4', '2-5', '2-6', '3-4', '3-5', '3-6'],
    '17-39' => ['1-7', '1-8', '1-9', '2-7', '2-8', '2-9', '3-7', '3-8', '3-9'],
    '41-63' => ['4-1', '4-2', '4-3', '5-1', '5-2', '5-3', '6-1', '6-2', '6-3'],
    '44-66' => ['4-4', '4-5', '4-6', '5-4', '5-5', '5-6', '6-4', '6-5', '6-6'],
    '47-69' => ['4-7', '4-8', '4-9', '5-7', '5-8', '5-9', '6-7', '6-8', '6-9'],
    '71-93' => ['7-1', '7-2', '7-3', '8-1', '8-2', '8-3', '9-1', '9-2', '9-3'],
    '74-96' => ['7-4', '7-5', '7-6', '8-4', '8-5', '8-6', '9-4', '9-5', '9-6'],
    '77-99' => ['7-7', '7-8', '7-9', '8-7', '8-8', '8-9', '9-7', '9-8', '9-9'],
];

solve($input);

function solve($input)
{
    $solve = parse($input);

    $runs = 1;
    do {
        $remainingValuesToFill = 0;
        for ($i = 1; $i < 10; $i++) {
            for ($j = 1; $j < 10; $j++) {
                if (is_array($solve[$i][$j])) {
                    $remainingValuesToFill++;
                    // remove possibilities that exist on the same row
                    $solve[$i][$j] = array_values(array_diff($solve[$i][$j], clean(array_values($solve[$i]))));
                    // remove possibilities that exist on the same col
                    $solve[$i][$j] = array_values(array_diff($solve[$i][$j], clean(array_column($solve, $j))));
                    // remove possibilities that exist in the same group
                    $solve[$i][$j] = array_values(array_diff($solve[$i][$j], clean(getGroup($solve, $i, $j))));
                    
                    // remove possibilities from other groups if a value is restricted only in one col
                    // remove possibilities from other groups if a value is restricted only in one row
                    foreach ($solve[$i][$j] as $possibleVal) {
                    	if (true) {
							foreach (['row', 'col'] as $type) {
								$removeValueFromFields = removeOptions($possibleVal, $solve, $i, $j, $type);
								if (count($removeValueFromFields)) {
									foreach ($removeValueFromFields as $key) {
										list($rk, $ck) = explode('-', $key);
										$solve[$rk][$ck] = array_values(array_diff($solve[$rk][$ck], [$possibleVal]));
									}
								}
							}
						} elseif (false) {
							foreach (['checkRow', 'checkColumn'] as $function) {
								$removeValueFromFields = $function($possibleVal, $solve, $i, $j);
								if (count($removeValueFromFields)) {
									foreach ($removeValueFromFields as $key) {
										list($rk, $ck) = explode('-', $key);
										$solve[$rk][$ck] = array_values(array_diff($solve[$rk][$ck], [$possibleVal]));
									}
								}
							}
						} else {
							$removeValueFromFields = checkRow($possibleVal, $solve, $i, $j);
							if (count($removeValueFromFields)) {
								foreach ($removeValueFromFields as $key) {
									list($rk, $ck) = explode('-', $key);
									$solve[$rk][$ck] = array_values(array_diff($solve[$rk][$ck], [$possibleVal]));
								}
							}

							$removeValueFromFields = checkColumn($possibleVal, $solve, $i, $j);
							if (count($removeValueFromFields)) {
								foreach ($removeValueFromFields as $key) {
									list($rk, $ck) = explode('-', $key);
									$solve[$rk][$ck] = array_values(array_diff($solve[$rk][$ck], [$possibleVal]));
								}
							}
						}
                    }

                    // fill in if it is the only possibility in the group
                    foreach ($solve[$i][$j] as $possibleVal) {
                        if (onlyOptionInGroup($possibleVal, $solve, $i, $j)) {
                            $solve[$i][$j] = $possibleVal;
                            $remainingValuesToFill--;
                            continue(2);
                        }
                    }

                    // fill in if it is the only remaining possibility
                    if (count($solve[$i][$j]) == 1) {
                        $solve[$i][$j] = reset($solve[$i][$j]);
                        $remainingValuesToFill--;
                    }
                }
            }
        }

        if ($runs++ > MAX_RUNS) {
            echo 'Couldn\'t solve puzzle in ' . MAX_RUNS . ' runs!' . PHP_EOL;
            display($solve);
            break;
        }
        
        if (SEE_ALL) {
            echo $runs . ' (' . $remainingValuesToFill . ')' . PHP_EOL;
            display($solve);
        } elseif (!$remainingValuesToFill) {
            display($solve);
        }
    } while ($remainingValuesToFill);
}

function removeOptions($value, $grid, $rowKey, $colKey, $type = 'row')
{
	$removeValueFromKeys = [];

	$pattern = "/^$rowKey-/";
	if ($type == 'col') {
		$pattern = "/-$colKey$/";
	}

	$$type = $total = 0;
	foreach (getGroup($grid, $rowKey, $colKey) as $key => $cell) {
		if (!is_array($cell) || !in_array($value, $cell)) {
			continue;
		}

		$total++;

		if (preg_match($pattern, $key)) {
			$$type++;
		}
	}

	if ($$type == $total) {
		$nextKeys = [];

		$key = $colKey;
		if ($type == 'col') {
			$key = $rowKey;
		}

		if ($key < 4) { // 1,2,3
			$nextKeys[] = $key + 3;
			$nextKeys[] = $key + 6;
		} elseif ($key > 6) { // 7,8,9
			$nextKeys[] = $key - 3;
			$nextKeys[] = $key - 6;
		} else { // 4,5,6
			$nextKeys[] = $key - 3;
			$nextKeys[] = $key + 6;
		}

		foreach ($nextKeys as $nextKey) {
			$rowNextKey = $rowKey;
			$colNextKey = $nextKey;
			if ($type == 'col') {
				$rowNextKey = $nextKey;
				$colNextKey = $colKey;
			}
			foreach (getGroup($grid, $rowNextKey, $colNextKey) as $key => $cell) {
				if (!is_array($cell) || !in_array($value, $cell)) {
					continue;
				}

				if (preg_match($pattern, $key)) {
					$removeValueFromKeys[] = $key;
				}
			}
		}
	}

	if (count($removeValueFromKeys) && SEE_ALL) {
		echo PHP_EOL . 'Remove ' . $value . ' from (' . $type . ') keys:' . PHP_EOL;
		print_r($removeValueFromKeys);
	}

	return $removeValueFromKeys;
}

function checkRow($value, $grid, $rowKey, $colKey)
{
	$removeValueFromKeys = [];

	$total = $inCol = 0;
	foreach (getGroup($grid, $rowKey, $colKey) as $key => $cell) {
		if (!is_array($cell) || !in_array($value, $cell)) {
			continue;
		}

		$total++;

		if (preg_match("/^$rowKey-/", $key)) {
			$inCol++;
		}
	}

	if ($inCol == $total) {
		$nextKeys = [];
		if ($colKey < 4) { // 1,2,3
			$nextKeys[] = $colKey + 3;
			$nextKeys[] = $colKey + 6;
		} elseif ($colKey > 6) { // 7,8,9
			$nextKeys[] = $colKey - 3;
			$nextKeys[] = $colKey - 6;
		} else { // 4,5,6
			$nextKeys[] = $colKey - 3;
			$nextKeys[] = $colKey + 6;
		}

		foreach ($nextKeys as $nextKey) {
			foreach (getGroup($grid, $rowKey, $nextKey) as $key => $cell) {
				if (!is_array($cell) || !in_array($value, $cell)) {
					continue;
				}

				if (preg_match("/^$rowKey-/", $key)) {
					$removeValueFromKeys[] = $key;
				}
			}
		}
	}

	if (count($removeValueFromKeys) && SEE_ALL) {
		echo PHP_EOL . 'Remove ' . $value . ' from (row) keys:' . PHP_EOL;
		print_r($removeValueFromKeys);
	}

	return $removeValueFromKeys;
}

function checkColumn($value, $grid, $rowKey, $colKey)
{
	$removeValueFromKeys = [];

	$total = $inRow = 0;
	foreach (getGroup($grid, $rowKey, $colKey) as $key => $cell) {
		if (!is_array($cell) || !in_array($value, $cell)) {
			continue;
		}

		$total++;

		if (preg_match("/-$colKey$/", $key)) {
			$inRow++;
		}
	}

	if ($inRow == $total) {
		$nextKeys = [];
		if ($rowKey < 4) { // 1,2,3
			$nextKeys[] = $rowKey + 3;
			$nextKeys[] = $rowKey + 6;
		} elseif ($rowKey > 6) { // 7,8,9
			$nextKeys[] = $rowKey - 3;
			$nextKeys[] = $rowKey - 6;
		} else { // 4,5,6
			$nextKeys[] = $rowKey - 3;
			$nextKeys[] = $rowKey + 6;
		}

		foreach ($nextKeys as $nextKey) {
			foreach (getGroup($grid, $nextKey, $colKey) as $key => $cell) {
				if (!is_array($cell) || !in_array($value, $cell)) {
					continue;
				}

				if (preg_match("/-$colKey$/", $key)) {
					$removeValueFromKeys[] = $key;
				}
			}
		}
	}

	if (count($removeValueFromKeys) && SEE_ALL) {
		echo PHP_EOL . 'Remove ' . $value . ' from (col) keys:' . PHP_EOL;
		print_r($removeValueFromKeys);
	}

	return $removeValueFromKeys;
}

function onlyOptionInGroup($value, $grid, $rowKey, $colKey)
{
    $possibleValueCount = 0;
    foreach (getGroup($grid, $rowKey, $colKey) as $cell) {
        if (is_array($cell) && in_array($value, $cell)) {
            $possibleValueCount++;
        }
    }

    return $possibleValueCount == 1;
}

function getGroup($grid, $rowKey, $colKey)
{
    $group = [];
    foreach (GROUPS as $groupKeys) {
        if (in_array($rowKey . '-' . $colKey, $groupKeys)) {
            foreach ($groupKeys as $key) {
                list($rk, $ck) = explode('-', $key);
                $group[$key] = $grid[$rk][$ck];
            }
        }
    }

    return $group;
}

function clean($array) {
    foreach ($array as $key => $elem) {
        if (is_array($elem)) {
            unset($array[$key]);
        }
    }

    return $array;
}

function display($grid)
{
    echo '|-----------------------|' . PHP_EOL;

    foreach ($grid as $rk => $row) {
        echo '| ';
        foreach ($row as $ck => $col) {
            echo (!is_array($col) && $col ? $col : ' ') . (in_array($ck, [3, 6]) ? ' |' : '') . ' ';
        }
        echo '| ';
        if (SEE_ALL) echo json_encode($row);;
        echo PHP_EOL;

        if (in_array($rk, [3, 6])) {
            echo '|-----------------------|' . PHP_EOL;
        }
    }

    echo '|-----------------------|' . PHP_EOL . PHP_EOL;
}

function parse($input)
{
    // display input
    echo 'Input:' . PHP_EOL . $input . PHP_EOL . PHP_EOL;

    $grid = [];
    foreach (explode("\n", trim($input)) as $rk => $row) {
        $grk = $rk + 1;

        if (!array_key_exists($grk, $grid)) {
            $grid[$grk] = [];
        }

        foreach (explode(' ', trim($row)) as $ck => $col) {
            $k = $ck + 1;
            
            if ($col) {
                $grid[$grk][$k] = $col;
            } else {
                $grid[$grk][$k] = range(1, 9);
            }
        }
    }

    display($grid);

    return $grid;
}

function valid($input)
{
    $rows = explode("\n", trim($input));

    foreach ($rows as $row) {
        if (!preg_match('/^([0-9] ?){8}[0-9]$/', trim($row))) {
            echo 'Invalid row: "' . $row . '"' . PHP_EOL;
            return false;
        }
    }

    if (count($rows) != 9) {
        return false;
    }

    return true;
}
