<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOAU | Student Activity View</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bsu-green: #2d6a4f;
            --bsu-dark: #1b4332;
            --bsu-mint: #d8f3dc;
            --text-dark: #1a1c1e;
            --text-muted: #5f6368;
            --border-color: #dadce0;
            --bg-page: #f0f2f5;
            --white: #ffffff;
        }

        /* Reduced Top Spacing */
        body { 
            font-family: 'Inter', -apple-system, sans-serif; 
            background-color: var(--bg-page); 
            color: var(--text-dark); 
            margin: 0; 
            padding: 20px;
            line-height: 1.5; 
        }

        .container { max-width: 100%; margin: 20px auto 0; padding: 0 10px; }

        /* Header Section */
        .view-header {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .view-header h2 { 
            margin: 0; 
            color: var(--bsu-dark); 
            font-size: 1.4rem;
            font-weight: 700;
        }

        /* Search Bar */
        .universal-search {
            width: 100%;
            max-width: 350px;
            padding: 10px 14px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.85rem;
            background: var(--white);
        }

        /* Table Wrapper */
        .table-wrapper {
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12);
            overflow-x: auto;
            border: 1px solid var(--border-color);
        }

        /* Auto-sizing Table Adjustments */
        table { 
            width: 100%; 
            border-collapse: collapse; 
            table-layout: auto; /* Dynamic sizing based on text length */
        }

        th { 
            background: #f8f9fa; 
            padding: 12px 10px; 
            font-size: 0.7rem; 
            text-transform: uppercase;
            font-weight: 700;
            border-bottom: 2px solid var(--bsu-mint);
            text-align: left;
            color: var(--bsu-dark);
            position: sticky; 
            top: 0;
            white-space: nowrap; /* Prevents column header text wrapping */
        }

        td { 
            padding: 10px;
            border-bottom: 1px solid #f0f0f0; 
            background: var(--white);
            font-size: 0.8rem;
            color: var(--text-dark);
            white-space: nowrap; /* Fits table cells precisely to text length */
        }

        tr:hover td { background-color: #f9fbf9; }

        /* Specific Cell Content Rules */
        .col-org { font-weight: 700; color: var(--bsu-green); }
        .col-title { font-weight: 600; max-width: 250px; }
        .col-remarks { color: var(--text-muted); max-width: 200px; }
        .col-center { text-align: center; }

        /* Handle long titles & remarks cleanly with ellipsis */
        .truncate {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: block;
        }

    </style>
</head>
<body>

 <?php include "views/partials/nav.php"; ?>

<div class="container">
    <div class="view-header">
        <h2>Organization Activity List</h2>
        <input type="text" id="tableSearch" class="universal-search" placeholder="Search activities..." onkeyup="filterTable()">
    </div>

    <div class="table-wrapper">
        <table id="soauTable" class="data-table">
            <thead>
                <tr>
                    <th>Permit ID</th>
                    <th class="col-org">Organization</th>
                    <th class="col-title">Activity Title</th>
                    <th>Type</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Venue</th>
                    <th>Appr. Date</th>
                    <th>Report Due</th>
                    <th>Actual Sub.</th>
                    <th class="col-center">Rating %</th>
                    <th class="col-center">AP +/-</th>
                    <th class="col-center">AR +/-</th>
                    <th class="col-remarks">Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($approved_permits)): ?>
                    <?php foreach ($approved_permits as $permit): ?>
                    <tr data-id="<?php echo htmlspecialchars($permit['permit_id'] ?? $permit['id']); ?>">
                        <td><?php echo htmlspecialchars($permit['permit_id'] ?? '-'); ?></td>
                        <td class="col-org"><?php echo htmlspecialchars($permit['organization'] ?? '-'); ?></td>
                        <td class="col-title">
                            <span class="truncate" title="<?php echo htmlspecialchars($permit['activity_title'] ?? '-'); ?>">
                                <?php echo htmlspecialchars($permit['activity_title'] ?? '-'); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($permit['type'] ?? $permit['campus_type'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($permit['start_date'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($permit['end_date'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($permit['start_time'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($permit['end_time'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($permit['venue'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($permit['approval_date'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($permit['report_due'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($permit['actual_submission'] ?? '-'); ?></td>
                        <td class="col-center"><?php echo htmlspecialchars($permit['rating'] ?? '-'); ?></td>
                        <td class="col-center"><?php echo htmlspecialchars($permit['ap_points'] ?? '-'); ?></td>
                        <td class="col-center"><?php echo htmlspecialchars($permit['ar_points'] ?? '-'); ?></td>
                        <td class="col-remarks">
                            <span class="truncate" title="<?php echo htmlspecialchars($permit['remarks'] ?? '-'); ?>">
                                <?php echo htmlspecialchars($permit['remarks'] ?? '-'); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="16" style="text-align: center; color: var(--text-muted); padding: 20px;">
                            No approved permits found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function filterTable() {
        const input = document.getElementById("tableSearch");
        const filter = input.value.toLowerCase();
        const tr = document.querySelectorAll("#soauTable tbody tr");
        tr.forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(filter) ? "" : "none";
        });
    }
</script>

</body>
</html>