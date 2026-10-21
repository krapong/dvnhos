
WITH
screen_data AS (
    SELECT
        vn,
        MAX(service_end_datetime) AS end_at
    FROM ovst_service_time
    WHERE ovst_service_time_type_code = 'OPD-SCREEN'
      AND service_date BETWEEN {{start}} AND {{end}}
    GROUP BY vn
),
doctor_data AS (
    SELECT
        vn,
        MIN(service_begin_datetime) AS start_at
    FROM ovst_service_time
    WHERE ovst_service_time_type_code = 'OPD-DOCTOR'
      AND service_date BETWEEN {{start}} AND {{end}}
    GROUP BY vn
),
doctor_queue AS (
    SELECT
        vn,
        MIN(queue_datetime) AS queue_at
    FROM opd_dep_queue
    WHERE depcode IN ('003','069','070','071','072','073')
      AND queue_datetime >= {{start}}
      AND queue_datetime < {{exclusive_end}}
    GROUP BY vn
),
pharmacy_queue AS (
    SELECT
        vn,
        MIN(queue_datetime) AS queue_at
    FROM opd_dep_queue
    WHERE depcode = '006'
      AND queue_datetime >= {{start}}
      AND queue_datetime < {{exclusive_end}}
    GROUP BY vn
),
pharmacy_end AS (
    SELECT
        vn,
        MAX(dispense_datetime) AS end_at
    FROM rx_stat
    WHERE dispense_datetime >= {{start}}
      AND dispense_datetime < {{dispense_end}}
    GROUP BY vn
),
lab_data AS (
    SELECT
        vn,
        MIN(TIMESTAMP(receive_date,receive_time)) AS start_at,
        MAX(TIMESTAMP(report_date,report_time)) AS end_at
    FROM lab_head
    WHERE order_date BETWEEN {{start}} AND {{end}}
      AND receive_time > '00:00:00'
      AND report_time > '00:00:00'
      AND receive_date = report_date
    GROUP BY vn
),
xray_data AS (
    SELECT
        vn,
        MIN(TIMESTAMP(request_date,request_time)) AS start_at,
        MAX(TIMESTAMP(
            confirm_radiology_date,
            confirm_radiology_time
        )) AS end_at
    FROM xray_report
    WHERE request_date BETWEEN {{start}} AND {{end}}
      AND request_time > '00:00:00'
      AND confirm_radiology_time > '00:00:00'
      AND request_date = confirm_radiology_date
    GROUP BY vn
),
base AS (
    SELECT
        (YEAR(o.vstdate) + 543 + (MONTH(o.vstdate) >= 10)) AS fy,

        TIMESTAMPDIFF(
            SECOND,
            TIMESTAMP(o.vstdate,o.vsttime),
            sc.end_at
        ) AS t_screen,

        TIMESTAMPDIFF(
            SECOND,
            dq.queue_at,
            d.start_at
        ) AS t_doctor,

        TIMESTAMPDIFF(
            SECOND,
            l.start_at,
            l.end_at
        ) AS t_lab,

        TIMESTAMPDIFF(
            SECOND,
            x.start_at,
            x.end_at
        ) AS t_xray,

        TIMESTAMPDIFF(
            SECOND,
            pq.queue_at,
            pe.end_at
        ) AS t_rx,

        TIMESTAMPDIFF(
            SECOND,
            TIMESTAMP(o.vstdate,o.vsttime),
            pe.end_at
        ) AS t_total

    FROM ovst o
    LEFT JOIN screen_data sc ON sc.vn = o.vn
    LEFT JOIN doctor_data d ON d.vn = o.vn
    LEFT JOIN doctor_queue dq ON dq.vn = o.vn
    LEFT JOIN pharmacy_queue pq ON pq.vn = o.vn
    LEFT JOIN pharmacy_end pe ON pe.vn = o.vn
    LEFT JOIN lab_data l ON l.vn = o.vn
    LEFT JOIN xray_data x ON x.vn = o.vn

    WHERE o.vstdate BETWEEN {{start}} AND {{end}}
      AND o.vsttime > '00:00:00'
),
summary AS (
    SELECT
        fy,
        AVG(CASE WHEN t_screen BETWEEN 0 AND 28800
            THEN t_screen END) AS a_screen,
        COUNT(CASE WHEN t_screen BETWEEN 0 AND 28800
            THEN 1 END) AS n_screen,

        AVG(CASE WHEN t_doctor BETWEEN 0 AND 28800
            THEN t_doctor END) AS a_doctor,
        COUNT(CASE WHEN t_doctor BETWEEN 0 AND 28800
            THEN 1 END) AS n_doctor,

        AVG(CASE WHEN t_lab BETWEEN 0 AND 28800
            THEN t_lab END) AS a_lab,
        COUNT(CASE WHEN t_lab BETWEEN 0 AND 28800
            THEN 1 END) AS n_lab,

        AVG(CASE WHEN t_xray BETWEEN 0 AND 28800
            THEN t_xray END) AS a_xray,
        COUNT(CASE WHEN t_xray BETWEEN 0 AND 28800
            THEN 1 END) AS n_xray,

        AVG(CASE WHEN t_rx BETWEEN 0 AND 14400
            THEN t_rx END) AS a_rx,
        COUNT(CASE WHEN t_rx BETWEEN 0 AND 14400
            THEN 1 END) AS n_rx,

        AVG(CASE WHEN t_total BETWEEN 0 AND 28800
            THEN t_total END) AS a_total,
        COUNT(CASE WHEN t_total BETWEEN 0 AND 28800
            THEN 1 END) AS n_total
    FROM base
    GROUP BY fy
)
SELECT
    CONCAT('ปีงบประมาณ ',fy) AS `ปีงบประมาณ`,

    SEC_TO_TIME(ROUND(a_screen))
        AS `จุดคัดกรอง/ซักประวัติ`,
    n_screen AS `จำนวนคัดกรอง`,

    SEC_TO_TIME(ROUND(a_doctor))
        AS `จุดรอพบแพทย์`,
    n_doctor AS `จำนวนรอแพทย์`,

    SEC_TO_TIME(ROUND(a_lab))
        AS `จุด Lab`,
    n_lab AS `จำนวน Lab`,

    SEC_TO_TIME(ROUND(a_xray))
        AS `จุด X-ray`,
    n_xray AS `จำนวน X-ray`,

    SEC_TO_TIME(ROUND(a_rx))
        AS `จุดรอรับยา`,
    n_rx AS `จำนวนรอรับยา`,

    SEC_TO_TIME(ROUND(a_total))
        AS `รวมทุกจุด`,
    n_total AS `จำนวนรวม`

FROM summary
ORDER BY fy;
