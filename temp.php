<?php

# Chris Eustis
# ECE 331 Project Two
# Due: May 2, 2016
# Read temperature from sqlite3 database and display a graph using gd.
# The graph displays data from the last 24 hours, as well as displaying
# the most recent temp as a string in the upper right corner of the graph.

# Setup image and graph
#
# Plot size
$height = 880;
$width = 1860;

$im = imagecreate($width, $height);
header("Content-type: image/png");

# Define some variables per the plot
$left_margin = 60;
$top_margin = 31;
$bottom_margin = 170;
$horiz_grid_gap = 20;
$vert_grid_gap = 40;
$data_24hrs = 1444;
$max_temp = 100;
$temp_gap = 1.25;
$date_format = 'M d h:i A';

# Define some colors
$white = imagecolorallocate($im, 255, 255, 255);
$gray = imagecolorallocate($im, 200, 200, 200);
$black = imagecolorallocate($im, 0, 0, 0);
$red = imagecolorallocate($im, 255, 0, 0);

# Plot boundaries (y then x)
imageline($im, $vert_grid_gap, 11, $vert_grid_gap, $height-$bottom_margin, $black);
imageline($im, $vert_grid_gap, $height - $bottom_margin, $width-10, $height - $bottom_margin, $black);

# Draw out the horizontal grid lines for the graph
#
$num = 0;
$y = $top_margin;
$horiz_lines = ($height - $bottom_margin - $top_margin) / $horiz_grid_gap;
while ($num < $horiz_lines) { 
	# Grid line
	imageline($im, $vert_grid_gap, $y, $width+111, $y, $gray);
	# Y axis label
	imagestring($im, 5, 1, $y-10, $max_temp, $red);
	$y += $horiz_grid_gap;
	$num++;
	$max_temp -=3;
}
# Draw out vertical grid lines for the graph
#
$x = $width - $vert_grid_gap;
# Line up vertical lines with datetime values
$line_pos = $left_margin+(65/2);
while($x) {
	# Grid line
	imageline($im, $line_pos, 11, $line_pos, $height-170, $gray);
	$line_pos += 50;
	$x -= $horiz_grid_gap;
}

# Get SQL data and print out values date values for the x-axis
$db = new SQLite3('readTemp/templogger.db') or die('Unable to open database');

# Read rows of data representing 24 hours
$sql = 'SELECT * FROM tempLog ORDER BY ID DESC LIMIT ' . $data_24hrs;
$db_data = $db->query($sql) or die('Query Failed');

# Get datetime values from db for x-axis label and slap them on there
$row_count = $data_24hrs;
$x = $width-$horiz_grid_gap;
while ($row_count) {
	$db_row = $db_data->fetchArray();
	$dt = date($date_format, strtotime(substr($db_row['datetime'], 0, 18)));

	# Only show every 40 minutes
	if ($row_count % $vert_grid_gap == 0 || $row_count == 3) {
		imagestringup($im, 5, $x, $height-25, $dt, $red);
	}
	$row_count--;
	$x -= $temp_gap;
}

# Get temperature values and plot the points
#
$sql = 'SELECT * FROM tempLog ORDER BY ID DESC LIMIT ' . $data_24hrs;
$db_data = $db->query($sql) or die('Query Failed');

# Calculate values for plotting the temperature
$first = 1;
$x = $width -15;
$x2 = $x - $temp_gap;
$row_count = $data_24hrs;
$height_diff = $height - $bottom_margin;
# Start getting data
$db_row = $db_data->fetchArray();
while ($row_count) {
	# First time through the database and need to get the first data point
	# as well as print the most recent temp in the upper right corner of the
	# plot.
	if ($first) {
		# Get temp for the plot
		$temp = $db_row['temperature'] * (9/5) + 32;
		# Calculate the first value from the database
		$y1 = $height_diff - ($temp / 3 * $horiz_grid_gap) - 13.32;
		# Place rectangle in upper right showing current reading of temp
		imagefilledrectangle($im, $width-100, $top_margin+10, $width-$horiz_grid_gap, $top_margin+60, $black);
		# Read temp and write it in rectangle (arbitrary location)
		imagestring($im, 6, $width-78, $top_margin+15, "Temp", $white);
		imagestring($im, 6, $width-82, $top_margin+35, ($db_row['temperature']*(9/5)+32) . " F", $white);
		$first = 0;
	} else {
		# Update points to create continuous plot
		$y1 = $y2;
	}
	# Get next row of data
	$db_row = $db_data->fetchArray();
	$temp = $db_row['temperature'] * (9/5) + 32;
	$y2 = $height_diff - ($temp / 3 * $horiz_grid_gap) - 13.32;

	# Put temp data on plot
	imageline($im, $x, $y1, $x2, $y2, $red);
	$row_count--;
	$x -= $temp_gap;
	$x2 -= $temp_gap;
}

imagepng($im);

?>
