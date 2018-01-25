<?php    
    require_once 'DbConnect.php';
    $Database = new DbConnect();
    $conn = $Database->dbConnection(); 
    $poster = 0;
    
    $poster = 1;
    $root = 5;
    $goal = 10;
    $mool = $root;          


    $query = $conn->query("SELECT * FROM city");
    $city_num = 0;
    while($row = $query->fetch(PDO::FETCH_ASSOC)){
        $city[$row['id']]['city'] = $row['city'];
        $city[$row['id']]['lat'] = $row['lat'];
        $city[$row['id']]['lng'] = $row['lng'];
        $city_num++;        
    }

    $query = $conn->query("SELECT node1,node2 FROM node ORDER BY distance");

    while($row = $query->fetch(PDO::FETCH_ASSOC)){
        $graph[$row['node1']][] = $row['node2'];
        $graph[$row['node2']][] = $row['node1'];
    } 

    $solution = 0;
    global $temporary_buffer;

    for($i=0; $i<=$city_num;$i++){
        $parent_node[$i] = -1;
    }


    /*
     * path finder
     */
    function Path($goal){
        global $parent_node;
        global $solution;
        global $temporary_buffer;

        if($parent_node[$goal]==-1){
            $temporary_buffer[$solution][]=$goal;
            return;
        }
        Path($parent_node[$goal]);
        $temporary_buffer[$solution][]=$goal;
    }

    /*
     * Simple Depth limited search
     */
    function DepthLimitedSearch($node, $goal, $depth, $parent){
        if($depth==0){
            if($node==$goal){
                global $solution;
                $solution++;
                Path($goal);
                return;
            }
            else return;
        }
        else if($depth>0){
            global $graph;
            global $mool;
            global $parent_node;
            $g_length = count($graph[$node]);

            for($i=0; $i<$g_length; $i++){
                $val = $graph[$node][$i];

                if($val==$mool || $val==$parent)
                    continue;
                $parent_node[$val] = $node;
                DepthLimitedSearch($val,$goal,$depth-1,$node);
            }
        }        
    }


    for($i=1; $i<=2; $i++) {
        DepthLimitedSearch($root,$goal,$i,$root);
    }

    if($solution>0){
        for($i=1; $i<=$solution; $i++){
            $distance = 0;
            for($j=0; $j+1<count($temporary_buffer[$i]); $j++){
                $node1 = $temporary_buffer[$i][$j];
                $node2 = $temporary_buffer[$i][$j+1];
                $query = $conn->query("SELECT distance FROM node WHERE (node1 = $node1 AND node2 = $node2)
                        OR (node1 = $node2  AND node2 = $node1)");
                $row = $query->fetch(PDO::FETCH_ASSOC);
                $distance += $row['distance'];
            }
            $temp[$distance] = $i;
            $sorted[] = $distance;
        }
        sort($sorted);
        $best_distance = $sorted[0];
        $solution = 0;

        for($i=0; $i<count($sorted); $i++){
            if($sorted[$i]>(2*$best_distance))
                break;
            $solution++;
            $index = $temp[$sorted[$i]];
            $buffer_length = count($temporary_buffer[$index]);

            for($j=0; $j<$buffer_length; $j++){
                $final_buffer[$i+1][$j] = $temporary_buffer[$index][$j];
            }
        }
    }
    $number_of_path = 0;
    $limit = 4;
    $used_path = 0;

    for($i=1; $i<=$solution; $i++){ 

        if($number_of_path>=$limit)
            break;

        $counter = count($final_buffer[$i]);

        if($counter==2){
            $used_path++;
            $from = $city[$final_buffer[$i][0]]['city'];
            $to = $city[$final_buffer[$i][1]]['city'];
            $query = $conn->query("SELECT * FROM bus WHERE (source = '$from' AND dest = '$to')
                    OR (source = '$to' AND dest = '$from') ORDER BY cost");

            while($row = $query->fetch(PDO::FETCH_ASSOC)){
                $number_of_path++;
                $path[$number_of_path][1]['way'] = $from.' to '.$to.' - '.$row['bus'];
                $path[$number_of_path][1]['fare'] = $row['fare'];
                $path[$number_of_path][1]['time'] = $row['time'];

                if($number_of_path>=$limit)
                    break;
            }
        } elseif ($counter==3) {
            $used_path++;
            $from = $city[$final_buffer[$i][0]]['city'];
            $to = $city[$final_buffer[$i][1]]['city'];
            $query_one = $conn->query("SELECT * FROM bus WHERE (source = '$from' AND dest = '$to')
                    OR (source = '$to' AND dest = '$from') ORDER BY cost");

            while($row_one = $query_one->fetch(PDO::FETCH_ASSOC)){
                if($number_of_path>=$limit)
                    break;

                $temporary['way'] = $from.' to '.$to.' - '.$row_one['bus'];
                $temporary['fare'] = $row_one['fare'];
                $temporary['time'] = $row_one['time'];

                $from2 = $city[$final_buffer[$i][1]]['city'];
                $to2 = $city[$final_buffer[$i][2]]['city'];
                $query_two = $conn->query("SELECT * FROM bus WHERE (source = '$from2' AND dest = '$to2')
                        OR (source = '$to2' AND dest = '$from2') ORDER BY cost");

                while($row_two = $query_two->fetch(PDO::FETCH_ASSOC)){
                    $number_of_path++;

                    $path[$number_of_path][1]['way'] = $temporary['way'];
                    $path[$number_of_path][1]['fare'] = $temporary['fare'];
                    $path[$number_of_path][1]['time'] = $temporary['time'];

                    $path[$number_of_path][2]['way'] = $from2.' to '.$to2.' - '.$row_two['bus'];
                    $path[$number_of_path][2]['fare'] = $row_two['fare'];
                    $path[$number_of_path][2]['time'] = $row_two['time'];

                    if($number_of_path==$limit)
                        break;
                }
            }                   
        }
    }
    
    echo $used_path;
    
    $map_count = 0;
    for($i=1; $i<=$used_path; $i++){        
        for($j=0; $j<count($final_buffer[$i]); $j++){
            $map_arry[$map_count]["title"] = $city[$final_buffer[$i][$j]]['city'];
            $map_arry[$map_count]["lat"] = $city[$final_buffer[$i][$j]]['lat'];
            $map_arry[$map_count]["lng"] = $city[$final_buffer[$i][$j]]['lng'];
            $map_count++;
        }        
    }
    
    echo json_encode($map_arry);