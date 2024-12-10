<?php

//***************EXECUTION***************//

do {
    $replay = true;
    playGame();
    echo "\033[0mPlay again? [y/n]\n";
    if (trim(fgets(STDIN)) == "n") {
        $replay = false;
    }
} while ($replay == true);

//***************FUNCTIONS***************//
//function playGame that combines all the logic
function playGame() {
    $flagCounter = 0;
    $mines = 5;
    $size_array = 8;

//select difficulty
    $difficulty = selectDifficulty();
    switch ($difficulty) {
        case "a":
            $mines = 1;
            $size_array = 4;
            break;
        case "b":
            $mines = 10;
            $size_array = 8;
            break;
        case "c":
            $mines = 40;
            $size_array = 16;
            break;
        case "d":
            $mines = 150;
            $size_array = 25;
    }

//create array 9x9
    //TODO Select difficulty level (bigger array and more bombs) 
    $arr = createArray($size_array);
    $arr_visible = createArray($size_array);
    printBoard($arr_visible, $flagCounter, $mines);

//random mines placement in array
    minePlacer($arr, $mines);

//check every cell of array for mines in adjacent cells (check 9 cells around) and increment the number in the current cell
//so that it shows how many mines are around it
    checkMines($arr);

//selection of cells logic
    while (true) {
//--> ask user to select a cell (check if in bounds of array) 
        //print pishtov
        //printBoard($arr, false);
        echo "***Select Coordinates***:\n";
        $user_x = getUserCoordinatesX($arr);
        $user_y = getUserCoordinatesY($arr);

        if (strcmp($arr_visible[$user_x][$user_y], "F") == 0) {
            //--> if a flag -> reverse to empty ("0")
            removeFlag($arr, $arr_visible, $user_x, $user_y);
            $flagCounter--;
            printBoard($arr_visible, $flagCounter, $mines);
        } else {
            //--> if a number -> reveal the cell
            if (is_numeric($arr[$user_x][$user_y]) && strcmp($arr[$user_x][$user_y], "0") != 0) {
                revealNumber($arr, $arr_visible, $user_x, $user_y);
            }
            // printBoard($arr_visible, $flagCounter);
            //--> if empty ("0") cell selected -> recursively reveal all other empty cells connected to eachother and first line of numbers
            //--> (base case - reach a cell that is not empty)
            if ($arr[$user_x][$user_y] == "0" && strcmp($arr_visible[$user_x][$user_y], "0") == 0) {
                revealEmpty($arr, $arr_visible, $user_x, $user_y, $flagCounter, $mines);
            }

            printBoard($arr_visible, $flagCounter, $mines);

            //--> if bomb selected -> game over, show all bombs
            if ($arr[$user_x][$user_y] === "B") {
                gameOver($arr, $arr_visible, $user_x, $user_y, $flagCounter, $mines);
                break;
            }
        }

        //--> after every selection check if there are any non-selected empty cells
        //--> if there are -> do nothing, else -> you won!
        if (!areEmptyCellsLeft($arr, $arr_visible)) {
            for ($i = 0; $i < count($arr); $i++) {
                for ($j = 0; $j < count($arr); $j++) {
                    if ($arr[$i][$j] == "B") {
                        $arr_visible[$i][$j] = "\033[1;92mB\033[0m";
                    }
                }
            }
            printBoard($arr_visible, $flagCounter, $mines);
            echo "\033[1;92m*********YOU WIN*********\n\033[0m";
            break;
        }

        //--> ask the player if he wants to mark a bomb after every round
        do {
            echo "Do you want to flag a bomb? [y/n]\n";
            $flag_answer = strtolower(trim(fgets(STDIN)));
            //--> if no -> continue loop to next iteration for selecting coordinates
            if (strcmp($flag_answer, "n") == 0) {
                break;
            }
            //--> if yes -> ask player for coordinates of the bomb he wants to mark
            else {
                echo "***Flag Coordinates***:\n";
                while(true){
                    $flag_x = getUserCoordinatesX($arr);
                    $flag_y = getUserCoordinatesY($arr);
                    if(strcmp($arr_visible[$flag_x][$flag_y], "0") == 0){
                        break;
                    } else{
                        echo "Please enter valid coordinates.\n";
                    }
                }
                $arr_visible[$flag_x][$flag_y] = "F";
                $flagCounter++;
                printBoard($arr_visible, $flagCounter, $mines);
            }
        } while (strcmp($flag_answer, "n") != 0);
    }
}

function createArray($size_array) {
    $arr = [];

    //fill array with empty spaces
    for ($rows = 0; $rows < $size_array; $rows++) {
        for ($cols = 0; $cols < $size_array; $cols++) {
            $arr[$rows][$cols] = "0";
        }
    }

    return $arr;
}

function getUserCoordinatesX(&$arr) {
    echo "Please enter X coordinates in range 0-" . (count($arr) - 1) . PHP_EOL;

    while (true) {
        $x = trim(fgets(STDIN));

        if ($x >= 0 && $x < count($arr) && is_numeric($x)) {
            $x = intval($x);
            return $x;
        } else {
            echo shell_exec("clear");
            echo "Please enter coordnates for X in range 0-" . (count($arr) - 1) . PHP_EOL;
        }
    }
}

function getUserCoordinatesY(&$arr) {
    echo "Please enter Y coordinates in range 0-" . (count($arr[0]) - 1) . PHP_EOL;

    while (true) {
        $y = trim(fgets(STDIN));

        if ($y >= 0 && $y < count($arr) && is_numeric($y)) {
            $y = intval($y);
            return $y;
        } else {
            echo shell_exec("clear");
            echo "Please enter coordinates for Y in range 0-" . (count($arr[0]) - 1) . PHP_EOL;
        }
    }
}

function printBoard(&$arr, &$flagCounter = 0, &$mines = 0, $clear = true) {
    //check to clear screen
    if ($clear) {
        echo shell_exec("clear");
    }

    //print flag counter
    echo "Flag counter: $flagCounter / $mines\n";
    echo PHP_EOL;

    //max digits count
    $board_rows = count($arr);
    $board_cols = count($arr[0]);
    $max_digits_rows = floor(log10($board_rows) + 1);
    $max_digits_cols = floor(log10($board_cols) + 1);

    //space before column indexes
    for ($i = 0; $i < $max_digits_cols; $i++) {
        echo " ";
    }
    echo "  ";

    //column indexes
    for ($i = 0; $i < count($arr); $i++) {
        $i_digids = $i !== 0 ? floor(log10($i) + 1) : 1; //get digids of current number
        for ($j = 0; $j < $max_digits_cols - $i_digids; $j++) {
            echo "\033[4;36m0"; //add 0 in front of the number if its has less digids than the max digids
        }
        echo "\033[4;36m" . $i . "\033[0m ";
    }
    echo PHP_EOL;
    echo PHP_EOL;

    //row indexes and cells print
    foreach ($arr as $rowIndex => $row) {
        //get digids of current number
        $rowIndex_digids = $rowIndex !== 0 ? floor(log10($rowIndex) + 1) : 1;

        //add 0 in front of the number if it has less digids than the max digids
        for ($j = 0; $j < $max_digits_rows - $rowIndex_digids; $j++) {
            echo "\033[4;36m0";
        }

        //print index
        echo "\033[4;36m" . $rowIndex . "\033[0m  ";

        //print cells
        foreach ($row as $k_col => $col) {
            echo $col;
            //add spaces after word to compensate for indentation
            for ($j = 0; $j < $max_digits_cols - 1; $j++) {
                echo " ";
            }
            if ($k_col != count($arr[0]) - 1) {
                echo "\u{2503}";
            }
        }
        echo PHP_EOL;

        //****HORIZONTAL GRID LINES****
        //print empty spaces before horizontal grid lines
        for ($i = 0; $i < $max_digits_cols; $i++) {
            echo " ";
        }
        echo "  ";
        //print crosses and horiontal symbols
        if ($rowIndex != count($arr) - 1) {
            foreach ($row as $k_col => $col) {
                if ($k_col != count($arr[0]) - 1) {
                    for ($j = 0; $j < $max_digits_rows + 1; $j++) {
                        echo $j == $max_digits_rows ? "\u{254b}" : "\u{2501}";
                    }
                } else {
                    echo "\u{2501}";
                }
            }
        }
        echo PHP_EOL;
    }
    echo PHP_EOL;
}

function revealNumber(&$arr, &$arr_visible, $user_x, $user_y) {
    if (is_numeric($arr[$user_x][$user_y]) && strcmp($arr[$user_x][$user_y], "0") != 0) {
        $arr_visible[$user_x][$user_y] = $arr[$user_x][$user_y];
    }
}

function removeFlag(&$arr, &$arr_visible, $user_x, $user_y) {
    if (strcmp($arr_visible[$user_x][$user_y], "F") == 0) {
        $arr_visible[$user_x][$user_y] = "0";
    }
}

function revealEmpty(&$arr, &$arr_visible, $user_x, $user_y, &$flagCounter, &$mines) {
    printBoard($arr_visible, $flagCounter, $mines);
    usleep(7500);

    if ($user_x >= 0 && $user_x < count($arr) && $user_y >= 0 && $user_y < count($arr[0])) {

        //base case - number reached -> print it and return;
        if (is_numeric($arr[$user_x][$user_y]) && $arr[$user_x][$user_y] != "0") {
            $arr_visible[$user_x][$user_y] = $arr[$user_x][$user_y];
            return;
        }

        if ($arr[$user_x][$user_y] == "0" && strcmp($arr_visible[$user_x][$user_y], "0") == 0) {
            $arr[$user_x][$user_y] = " ";
            $arr_visible[$user_x][$user_y] = " ";

            //up
            revealEmpty($arr, $arr_visible, $user_x - 1, $user_y, $flagCounter, $mines);

            //down
            revealEmpty($arr, $arr_visible, $user_x + 1, $user_y, $flagCounter, $mines);

            //left
            revealEmpty($arr, $arr_visible, $user_x, $user_y - 1, $flagCounter, $mines);

            //right
            revealEmpty($arr, $arr_visible, $user_x, $user_y + 1, $flagCounter, $mines);
        }
    }
}

function checkMines(&$arr) {
    for ($i = 0; $i < count($arr); $i++) {
        for ($j = 0; $j < count($arr[0]); $j++) {
            //skip if current cell is a mine
            if ($arr[$i][$j] == "B") {
                continue;
            }
            // add count near bombs
            $arr[$i][$j] = countMines($arr, $i, $j);
        }
    }
}

function minePlacer(&$arr, $mines = 10) {
    while ($mines > 0) {
        $randRow = rand(0, count($arr) - 1);
        $randCol = rand(0, count($arr) - 1);

        if ($arr[$randRow][$randCol] !== "B") { //\u{1F4A3}
            $arr[$randRow][$randCol] = "B";
            $mines--;
        }
    }
}

function gameOver(&$arr, &$arr_visible, $user_x, $user_y, $flagCounter, $mines) {
    for ($row = 0; $row < count($arr); $row++) {
        for ($col = 0; $col < count($arr[$row]); $col++) {
            if ($arr[$row][$col] === "B") {
                $arr_visible[$row][$col] = "\033[1;91mB\033[0m";
            }
        }
    }
    printBoard($arr_visible, $flagCounter, $mines);
    echo "\033[1;91m*********GAME OVER*********\n\033[0m";
}

function countMines(&$arr, $i, $j) {
    //TODO Refactor 2 vlojeni if-a da sa v edin if
    $minesCount = 0;
    //left
    if ($j - 1 >= 0) {
        if (strcmp($arr[$i][$j - 1], "B") == 0) {
            $minesCount++;
        }
    }
    //right
    if ($j + 1 < count($arr[0])) {
        if (strcmp($arr[$i][$j + 1], "B") == 0) {
            $minesCount++;
        }
    }
    // up left
    if ($i - 1 >= 0 && $j - 1 >= 0) {
        if (strcmp($arr[$i - 1][$j - 1], "B") == 0) {
            $minesCount++;
        }
    }
    //up
    if ($i - 1 >= 0) {
        if (strcmp($arr[$i - 1][$j], "B") == 0) {
            $minesCount++;
        }
    }
    //up right
    if ($i - 1 >= 0 && $j + 1 < count($arr[0])) {
        if (strcmp($arr[$i - 1][$j + 1], "B") == 0) {
            $minesCount++;
        }
    }
    // down left
    if ($i + 1 < count($arr) && $j - 1 >= 0) {
        if (strcmp($arr[$i + 1][$j - 1], "B") == 0) {
            $minesCount++;
        }
    }
    //down
    if ($i + 1 < count($arr)) {
        if (strcmp($arr[$i + 1][$j], "B") == 0) {
            $minesCount++;
        }
    }
    //down right
    if ($i + 1 < count($arr) && $j + 1 < count($arr[0])) {
        if (strcmp($arr[$i + 1][$j + 1], "B") == 0) {
            $minesCount++;
        }
    }

    return $minesCount;
}

function areEmptyCellsLeft(&$arr, &$arr_visible) {
    $areEmptyCellsLeft = false;

    for ($i = 0; $i < count($arr); $i++) {
        for ($j = 0; $j < count($arr[0]); $j++) {
            if (strcmp($arr_visible[$i][$j], "0") == 0 && strcmp($arr[$i][$j], "B") != 0) {
                $areEmptyCellsLeft = true;
                break 2;
            }
        }
    }

    return $areEmptyCellsLeft;
}

function selectDifficulty() {
    echo"a) Rumen\nb) Beginner\nc) Normal\nd) Hard\n";
    echo "Please select difficulty [a, b, c or d]:\n";

    while (true) {
        $difficulty = strtolower(trim(fgets(STDIN)));
        if (strcmp($difficulty, "a") == 0 || strcmp($difficulty, "b") == 0 || strcmp($difficulty, "c") == 0 || strcmp($difficulty, "d") == 0) {
            return $difficulty;
        } else {
            echo "\033[91mPlease enter a, b, c or d!\n\033[0m";
        }
    }
}
