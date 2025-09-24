<?php // content="text/plain; charset=utf-8"
//generates a disk space graph
include('../lib/jpgraph/jpgraph.php');
include('../lib/jpgraph/jpgraph_pie.php');

$free = disk_free_space("/");
$total = disk_total_space("/");

$percentage = round(($free / $total) * 100, 1);

$data = array($percentage, 100-$percentage);

$graph = new PieGraph(350, 250);

$theme_class = "DefaultTheme";

$graph->title->Set("Disk space: Blue - free space");
$graph->SetBox(true);

$p1 = new PiePlot($data);
$graph->Add($p1);

$p1->ShowBorder();
$p1->SetColor('black');
$p1->SetSliceColors(array('#1E90FF', '#DC143C'));
$graph->Stroke();
?>  