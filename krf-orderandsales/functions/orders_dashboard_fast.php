<?php
/** Scoped dashboard reader. Original orders_functions.php remains unchanged. */
function getPendingProvisionalSalesOrdersDetailedFast(
    $conn,
    $authconn,
    $companyid,
    $subcompanyid,
    $projectid,
    $shortby = "all",
    $shortbyvalue = "all",
    $loginroleid = 0,
    $loginid = ""
) {
    $data = [];

    try {
        $companyid    = trim((string)$companyid);
        $subcompanyid = trim((string)$subcompanyid);
        $projectid    = (int)$projectid;
        $loginroleid  = (int)$loginroleid;
        $loginid      = trim((string)$loginid);

        if ( $companyid === '' || $subcompanyid === '' ||  $projectid <= 0
        ) {
            throw new Exception(
                "Company, subcompany and project are required."
            );
        }

        /*
         * Items remain as individual rows.
         * Order and advance totals are calculated separately.
         */
        $baseQuery = "
            SELECT
                o.id,
                o.psono,
                o.companyid,
                o.subcompanyid,
                o.projectid,
                o.clientid,
                o.clientname,
                o.emailid,
                o.delivery_address,
                o.districtname,
                o.statename,
                o.mobileno,
                o.payment_terms,
                o.remarks,
                o.status,
                o.pickupfrom,
                o.created_by,
                o.created_at,
                o.updated_by,
                o.updated_at,

                i.product_name,
                i.quantity,
                i.expecteddeliverydate,
                COALESCE(i.amount, 0) AS item_amount,

                COALESCE(
                    item_total.orderamount,
                    0
                ) AS orderamount,

                COALESCE(
                    advance_total.advanceamt,
                    0
                ) AS advanceamt,

                (
                    COALESCE(
                        item_total.orderamount,
                        0
                    )
                    -
                    COALESCE(
                        advance_total.advanceamt,
                        0
                    )
                ) AS balanceamt,
                o.mobileno

            FROM krf_provisional_sales_order AS o

            LEFT JOIN krf_provisional_sales_order_items AS i
                ON i.psono = o.psono


            LEFT JOIN
            (
                SELECT
                    psono,
                    SUM(
                        COALESCE(amount, 0)
                    ) AS orderamount
                FROM krf_provisional_sales_order_items AS totals_i
                WHERE LOWER(TRIM(COALESCE(totals_i.status, ''))) NOT IN ('cancelled', 'cancel', 'remove', 'removed')
                  AND EXISTS (
                    SELECT 1 FROM krf_provisional_sales_order AS scope_o
                    WHERE scope_o.psono = totals_i.psono
                      AND scope_o.companyid = ?
                      AND scope_o.subcompanyid = ?
                      AND scope_o.projectid = ?
                )
                GROUP BY psono
            ) AS item_total
                ON item_total.psono = o.psono

            LEFT JOIN
            (
                SELECT
                    invoiceno,
                    clientid,
                    SUM(
                        COALESCE(advanceamount, 0)
                    ) AS advanceamt
                FROM advance_payment_transactions AS totals_a
                WHERE (totals_a.tedit IS NULL OR totals_a.tedit = '')
                  AND EXISTS (
                    SELECT 1 FROM krf_provisional_sales_order AS scope_o
                    WHERE scope_o.psono = totals_a.invoiceno
                      AND scope_o.clientid = totals_a.clientid
                      AND scope_o.companyid = ?
                      AND scope_o.subcompanyid = ?
                      AND scope_o.projectid = ?
                  )
                GROUP BY
                    invoiceno,
                    clientid
            ) AS advance_total
                ON advance_total.invoiceno = o.psono
               AND advance_total.clientid = o.clientid

            WHERE (
                    o.status IN ( 
                        'Draft',
						'Pending Approval',
                        'Emergency Sowing',
                        'Confirmed',
                        'Cancelled',
                        ''
                    )
                    OR o.status IS NULL
                )
              AND o.companyid = ?
              AND o.subcompanyid = ?
              AND o.projectid = ?

        ";

        $types = "ssississi";

        $params = [
            $companyid, $subcompanyid, $projectid, // item totals scope
            $companyid, $subcompanyid, $projectid, // advances scope
            $companyid, $subcompanyid, $projectid  // main orders
        ];

        /*
         * Role restriction.
         */
        // if ($loginroleid > 3) {
        //     if ($loginid === '') {
        //         return [];
        //     }

        //     $baseQuery .= "
        //         AND o.created_by = ?
        //     ";

        //     $types .= "s";
        //     $params[] = $loginid;
        // }

        /*
         * Additional filters.
         */
        switch ($shortby) {
            case "psono":
                $baseQuery .= " AND o.psono = ?";
                $types .= "s";
                $params[] = (string)$shortbyvalue;
                break;

            case "id":
            case "sno":
                $baseQuery .= " AND o.id = ?";
                $types .= "i";
                $params[] = (int)$shortbyvalue;
                break;

            case "status":
                $baseQuery .= " AND o.status = ?";
                $types .= "s";
                $params[] = (string)$shortbyvalue;
                break;

            case "likelyclientname":
                $baseQuery .= " AND o.clientname LIKE ?";
                $types .= "s";
                $params[] = "%" . $shortbyvalue . "%";
                break;

            case "clientname":
                $baseQuery .= " AND o.clientname = ?";
                $types .= "s";
                $params[] = (string)$shortbyvalue;
                break;

            case "onlyconfirmedWithclientname":
                $baseQuery .= "
                    AND o.status = 'Confirmed'
                    AND o.clientname = ?
                ";
                $types .= "s";
                $params[] = (string)$shortbyvalue;
                break;

            case "onlyconfirmedWithclientid":
                $baseQuery .= "
                    AND o.status = 'Confirmed'
                    AND o.clientid = ?
                ";
                $types .= "s";
                $params[] = (string)$shortbyvalue;
                break;

            case "onlydraftWithclientname":
                $baseQuery .= "
                    AND o.status = 'Draft'
                    AND o.clientname = ?
                ";
                $types .= "s";
                $params[] = (string)$shortbyvalue;
                break;

            case "onlydraftWithclientid":
                $baseQuery .= "
                    AND o.status = 'Draft'
                    AND o.clientid = ?
                ";
                $types .= "s";
                $params[] = (string)$shortbyvalue;
                break;

            case "onlyconfirmedWithmobileno":
                $baseQuery .= "
                    AND o.status = 'Confirmed'
                    AND o.mobileno = ?
                ";
                $types .= "s";
                $params[] = (string)$shortbyvalue;
                break;

            case "onlydraftWithmobileno":
                $baseQuery .= "
                    AND o.status = 'Draft'
                    AND o.mobileno = ?
                ";
                $types .= "s";
                $params[] = (string)$shortbyvalue;
                break;

            case "mobileno":
                $baseQuery .= " AND o.mobileno = ?";
                $types .= "s";
                $params[] = (string)$shortbyvalue;
                break;
        }

        $baseQuery .= "
             ORDER BY
                o.created_at ASC,
                i.expecteddeliverydate ASC,
                o.psono ASC
        ";

        $stmt = $conn->prepare($baseQuery);

        if (!$stmt) {
            throw new Exception(
                "Main query prepare failed: " .
                $conn->error
            );
        }

        $stmt->bind_param(
            $types,
            ...$params
        );

        if (!$stmt->execute()) {
            throw new Exception(
                "Main query execution failed: " .
                $stmt->error
            );
        }

        $result = $stmt->get_result();
        $userLookupIds = [];

        while ($row = $result->fetch_assoc()) {
            $createdBy = trim((string)($row["created_by"] ?? ""));
            $updatedBy = trim((string)($row["updated_by"] ?? ""));

            $row["loginname"]      = $createdBy;
            $row["loginroleid"]    = "";
            $row["updatedby_name"] = $updatedBy;

            if ($createdBy !== "") {
                $userLookupIds[$createdBy] = $createdBy;
            }
            if ($updatedBy !== "") {
                $userLookupIds[$updatedBy] = $updatedBy;
            }

            $data[] = $row;
        }

        $stmt->close();

        /*
         * Return main data when there are no user IDs.
         */
        if (empty($data) || empty($userLookupIds)) {
            return $data;
        }

        /*
         * Fetch all required users in one auth query.
         */
        $userLookupIds = array_values($userLookupIds);

        $placeholders = implode(
            ",",
            array_fill(
                0,
                count($userLookupIds),
                "?"
            )
        );

        $authQuery = "
            SELECT
                loginid,
                IFNULL(loginname, '') AS loginname,
                loginroleid
            FROM login_user_master
            WHERE loginid IN ($placeholders)
        ";

        $authStmt = $authconn->prepare($authQuery);

        if ($authStmt) {
            $authTypes = str_repeat("s", count($userLookupIds));
            $authStmt->bind_param($authTypes, ...$userLookupIds);

            if ($authStmt->execute()) {
                $authResult = $authStmt->get_result();
                $loginUsers = [];

                while ($user = $authResult->fetch_assoc()) {
                    $userLoginId = trim((string)$user["loginid"]);
                    $loginUsers[$userLoginId] = [
                        "loginname"   => $user["loginname"] ?? "",
                        "loginroleid" => $user["loginroleid"] ?? ""
                    ];
                }

                /*
                 * Merge login details into order rows.
                 */
                foreach ($data as &$row) {
                    $createdBy = trim((string)($row["created_by"] ?? ""));
                    $updatedBy = trim((string)($row["updated_by"] ?? ""));

                    if (isset($loginUsers[$createdBy]) && $loginUsers[$createdBy]["loginname"] !== '') {
                        $row["loginname"]   = $loginUsers[$createdBy]["loginname"];
                        $row["loginroleid"] = $loginUsers[$createdBy]["loginroleid"];
                    }

                    if (isset($loginUsers[$updatedBy]) && $loginUsers[$updatedBy]["loginname"] !== '') {
                        $row["updatedby_name"] = $loginUsers[$updatedBy]["loginname"];
                    }
                }
                unset($row);
            }
            $authStmt->close();
        }

        return $data;

    } catch (Throwable $e) {
        error_log(
            "getPendingProvisionalSalesOrdersDetailedFast error: " .
            $e->getMessage()
        );

        return [];
    }
}
