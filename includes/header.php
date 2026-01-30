<?php
include_once __DIR__ . '/../includes/confirm_delete.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>
        <?php 
            if (!empty($data['title'])){
                echo $data['title'];
            } else if (!empty($title)){
                echo $title;
            } else {
                echo 'เว็บจัดการงบประมาณการวิจัย';
            }

?>
    </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบบริหารงานวิจัย (Neon Admin)</title>

    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    screens: {
                        'fit': '1467px',
                    },
                    fontFamily: {
                        sarabun: ['Sarabun', 'sans-serif'],
                    },
                    colors: {
                        neon: {
                            pink: '#ec4899',
                            cyan: '#22d3ee',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        
    </style>
</head>

