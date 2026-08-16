<?php
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Maze Word';
$string['modulename'] = 'Maze Word';
$string['modulenameplural'] = 'Maze Words';
$string['thereareno'] = 'No {$a} available';
$string['mazeword:addinstance'] = 'Add a new Maze Word activity';
$string['mazeword:view'] = 'View Maze Word activity';
$string['mazeword:attempt'] = 'Attempt Maze Word activity';
$string['mazeword:manageattempts'] = 'Manage Maze Word attempts';
$string['mazeword:configure'] = 'Configure Maze Word activity';

// Form strings.
$string['mazewordsettings'] = 'Maze Word Settings';
$string['solutionword'] = 'Solution word';
$string['solutionword_help'] = 'The word that students must find by navigating the correct path through the maze. This word will be placed along the solution path.';
$string['wrongword1'] = 'Distractor word 1';
$string['wrongword1_help'] = 'A wrong word placed in a dead-end branch of the maze. Students who take the wrong path will see these letters.';
$string['wrongword2'] = 'Distractor word 2';
$string['wrongword3'] = 'Distractor word 3';
$string['mazesize'] = 'Maze size';
$string['mazesize_help'] = 'The size of the maze grid (NxN). Larger mazes are more challenging. Default is 20x20.';
$string['wordtooshort'] = 'Word must be at least 3 characters long.';
$string['wordtoolong'] = 'Word must not exceed 30 characters.';
$string['wordonlyletters'] = 'Word must contain only letters (A-Z, Ä, Ö, Ü). ß is converted to SS.';
$string['wordmustdiffer'] = 'This word must be different from the solution word.';

// View page strings.
$string['pluginadministration'] = 'Maze Word administration';
$string['pluginheading'] = 'Maze Word Plugin';
$string['modulename_help'] = 'Maze Word is an interactive labyrinth puzzle game where students navigate through a maze to find the path that spells out a solution word.';
$string['studentview'] = 'Student View';
$string['teacherview'] = 'Teacher View';
$string['solutionpath'] = 'Solution path';
$string['solutionpath_help'] = 'The path through the maze that spells out the solution word.';
$string['distractorpaths'] = 'Distractor paths';
$string['distractorpaths_help'] = 'Wrong paths in the maze that contain distractor words.';
$string['mazegrid'] = 'Maze Grid';
$string['start'] = 'Start';
$string['goal'] = 'Goal';
$string['startgame'] = 'Start Game';
$string['neustart'] = 'Restart';
$string['gamelost'] = 'Game Over!';
$string['gamewon'] = 'Congratulations!';
$string['solutionwas'] = 'The solution was: {$a}';
$string['completed'] = 'Completed';
$string['inprogress'] = 'In progress';
$string['notattempted'] = 'Not attempted';
$string['deleteallattempts'] = 'Delete all maze attempts';
$string['totalenrolled'] = 'Total enrolled students';
$string['totalattempts'] = 'Total attempts';
$string['completedattempts'] = 'Completed attempts';

// Completion strings.
$string['completiondetail:view'] = 'View the maze';
$string['completiondetail:complete'] = 'Complete the maze';

// Event strings.
$string['eventcoursemoduleviewed'] = 'Maze Word viewed';
$string['eventattemptcreated'] = 'Maze Word attempt created';
$string['eventattemptcompleted'] = 'Maze Word attempt completed';

// Privacy strings.
$string['privacy:metadata:mazeword_attempts'] = 'Stores maze attempt data for students.';
$string['privacy:metadata:mazeword_attempts:userid'] = 'The ID of the user who made the attempt.';
$string['privacy:metadata:mazeword_attempts:completed'] = 'Whether the attempt was completed.';
$string['privacy:metadata:mazeword_attempts:playerx'] = 'Last player X position.';
$string['privacy:metadata:mazeword_attempts:playery'] = 'Last player Y position.';
$string['privacy:metadata:mazeword_attempts:visitedcells'] = 'JSON list of visited cells.';
$string['privacy:metadata:mazeword_attempts:solutionvisited'] = 'JSON list of visited solution word cells.';
