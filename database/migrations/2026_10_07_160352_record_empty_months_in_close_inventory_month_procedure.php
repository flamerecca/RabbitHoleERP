<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    /**
     * Replace the procedure to record every closed month in inventory_month_closings, also when it has no snapshot rows,
     * and to check the previous month against those records.
     *
     * The closing rules live in this procedure on purpose, see docs/architecture.md section 4.11.
     */
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE PROCEDURE close_inventory_month(p_team_id bigint, p_period text, p_user_id bigint)
LANGUAGE plpgsql
AS $$
DECLARE
    v_start timestamp;
    v_end timestamp;
    v_previous text;
    v_has_previous boolean;
    v_pair record;
    v_opening_quantity numeric(15, 4);
    v_opening_amount numeric(15, 4);
    v_in_quantity numeric(15, 4);
    v_in_amount numeric(15, 4);
    v_out_quantity numeric(15, 4);
    v_out_amount numeric(15, 4);
    v_closing_quantity numeric(15, 4);
    v_closing_amount numeric(15, 4);
    v_average_cost numeric(15, 4);
BEGIN
    v_start := to_date(p_period || '-01', 'YYYY-MM-DD')::timestamp;
    v_end := v_start + interval '1 month';
    v_previous := to_char(v_start - interval '1 month', 'YYYY-MM');

    IF v_start >= date_trunc('month', CURRENT_DATE) THEN
        RAISE EXCEPTION 'RH001: The month % is not over yet.', p_period;
    END IF;

    IF EXISTS (SELECT 1 FROM inventory_month_closings WHERE team_id = p_team_id AND period < p_period)
        AND NOT EXISTS (SELECT 1 FROM inventory_month_closings WHERE team_id = p_team_id AND period = v_previous) THEN
        RAISE EXCEPTION 'RH002: Close % before %.', v_previous, p_period;
    END IF;

    v_has_previous := EXISTS (SELECT 1 FROM inventory_month_closings WHERE team_id = p_team_id AND period = v_previous);

    DELETE FROM inventory_month_snapshots WHERE team_id = p_team_id AND period = p_period;

    INSERT INTO inventory_month_closings (team_id, period, closed_by, closed_at, created_at, updated_at)
    VALUES (p_team_id, p_period, p_user_id, now(), now(), now())
    ON CONFLICT (team_id, period) DO UPDATE SET closed_by = EXCLUDED.closed_by, closed_at = EXCLUDED.closed_at, updated_at = now();

    FOR v_pair IN
        SELECT DISTINCT warehouse_id, product_id
        FROM stock_movements
        WHERE team_id = p_team_id AND created_at < v_end
        ORDER BY warehouse_id, product_id
    LOOP
        IF v_has_previous THEN
            SELECT closing_quantity, closing_amount
            INTO v_opening_quantity, v_opening_amount
            FROM inventory_month_snapshots
            WHERE team_id = p_team_id
                AND period = v_previous
                AND warehouse_id = v_pair.warehouse_id
                AND product_id = v_pair.product_id;
        ELSE
            SELECT SUM(quantity), SUM(CASE WHEN quantity > 0 THEN ABS(total_cost) ELSE -ABS(total_cost) END)
            INTO v_opening_quantity, v_opening_amount
            FROM stock_movements
            WHERE team_id = p_team_id
                AND warehouse_id = v_pair.warehouse_id
                AND product_id = v_pair.product_id
                AND created_at < v_start;
        END IF;

        v_opening_quantity := COALESCE(v_opening_quantity, 0);
        v_opening_amount := COALESCE(v_opening_amount, 0);

        SELECT COALESCE(SUM(quantity), 0), COALESCE(SUM(ABS(total_cost)), 0)
        INTO v_in_quantity, v_in_amount
        FROM stock_movements
        WHERE team_id = p_team_id
            AND warehouse_id = v_pair.warehouse_id
            AND product_id = v_pair.product_id
            AND created_at >= v_start
            AND created_at < v_end
            AND quantity > 0;

        SELECT COALESCE(SUM(-quantity), 0), COALESCE(SUM(ABS(total_cost)), 0)
        INTO v_out_quantity, v_out_amount
        FROM stock_movements
        WHERE team_id = p_team_id
            AND warehouse_id = v_pair.warehouse_id
            AND product_id = v_pair.product_id
            AND created_at >= v_start
            AND created_at < v_end
            AND quantity < 0;

        v_closing_quantity := v_opening_quantity + v_in_quantity - v_out_quantity;
        v_closing_amount := v_opening_amount + v_in_amount - v_out_amount;

        IF v_closing_quantity = 0 AND v_closing_amount = 0 AND v_in_quantity = 0 AND v_out_quantity = 0 THEN
            CONTINUE;
        END IF;

        IF v_closing_quantity > 0 THEN
            v_average_cost := round(v_closing_amount / v_closing_quantity, 4);
        ELSE
            v_average_cost := 0;
        END IF;

        INSERT INTO inventory_month_snapshots (
            team_id, period, warehouse_id, product_id,
            opening_quantity, opening_amount, in_quantity, in_amount, out_quantity, out_amount,
            closing_quantity, closing_amount, average_cost, closed_by, closed_at, created_at, updated_at
        ) VALUES (
            p_team_id, p_period, v_pair.warehouse_id, v_pair.product_id,
            v_opening_quantity, v_opening_amount, v_in_quantity, v_in_amount, v_out_quantity, v_out_amount,
            v_closing_quantity, v_closing_amount, v_average_cost, p_user_id, now(), now(), now()
        );
    END LOOP;
END;
$$
SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE PROCEDURE close_inventory_month(p_team_id bigint, p_period text, p_user_id bigint)
LANGUAGE plpgsql
AS $$
DECLARE
    v_start timestamp;
    v_end timestamp;
    v_previous text;
    v_has_previous boolean;
    v_pair record;
    v_opening_quantity numeric(15, 4);
    v_opening_amount numeric(15, 4);
    v_in_quantity numeric(15, 4);
    v_in_amount numeric(15, 4);
    v_out_quantity numeric(15, 4);
    v_out_amount numeric(15, 4);
    v_closing_quantity numeric(15, 4);
    v_closing_amount numeric(15, 4);
    v_average_cost numeric(15, 4);
BEGIN
    v_start := to_date(p_period || '-01', 'YYYY-MM-DD')::timestamp;
    v_end := v_start + interval '1 month';
    v_previous := to_char(v_start - interval '1 month', 'YYYY-MM');

    IF v_start >= date_trunc('month', CURRENT_DATE) THEN
        RAISE EXCEPTION 'RH001: The month % is not over yet.', p_period;
    END IF;

    IF EXISTS (SELECT 1 FROM inventory_month_snapshots WHERE team_id = p_team_id AND period < p_period)
        AND NOT EXISTS (SELECT 1 FROM inventory_month_snapshots WHERE team_id = p_team_id AND period = v_previous) THEN
        RAISE EXCEPTION 'RH002: Close % before %.', v_previous, p_period;
    END IF;

    v_has_previous := EXISTS (SELECT 1 FROM inventory_month_snapshots WHERE team_id = p_team_id AND period = v_previous);

    DELETE FROM inventory_month_snapshots WHERE team_id = p_team_id AND period = p_period;

    FOR v_pair IN
        SELECT DISTINCT warehouse_id, product_id
        FROM stock_movements
        WHERE team_id = p_team_id AND created_at < v_end
        ORDER BY warehouse_id, product_id
    LOOP
        IF v_has_previous THEN
            SELECT closing_quantity, closing_amount
            INTO v_opening_quantity, v_opening_amount
            FROM inventory_month_snapshots
            WHERE team_id = p_team_id
                AND period = v_previous
                AND warehouse_id = v_pair.warehouse_id
                AND product_id = v_pair.product_id;
        ELSE
            SELECT SUM(quantity), SUM(CASE WHEN quantity > 0 THEN ABS(total_cost) ELSE -ABS(total_cost) END)
            INTO v_opening_quantity, v_opening_amount
            FROM stock_movements
            WHERE team_id = p_team_id
                AND warehouse_id = v_pair.warehouse_id
                AND product_id = v_pair.product_id
                AND created_at < v_start;
        END IF;

        v_opening_quantity := COALESCE(v_opening_quantity, 0);
        v_opening_amount := COALESCE(v_opening_amount, 0);

        SELECT COALESCE(SUM(quantity), 0), COALESCE(SUM(ABS(total_cost)), 0)
        INTO v_in_quantity, v_in_amount
        FROM stock_movements
        WHERE team_id = p_team_id
            AND warehouse_id = v_pair.warehouse_id
            AND product_id = v_pair.product_id
            AND created_at >= v_start
            AND created_at < v_end
            AND quantity > 0;

        SELECT COALESCE(SUM(-quantity), 0), COALESCE(SUM(ABS(total_cost)), 0)
        INTO v_out_quantity, v_out_amount
        FROM stock_movements
        WHERE team_id = p_team_id
            AND warehouse_id = v_pair.warehouse_id
            AND product_id = v_pair.product_id
            AND created_at >= v_start
            AND created_at < v_end
            AND quantity < 0;

        v_closing_quantity := v_opening_quantity + v_in_quantity - v_out_quantity;
        v_closing_amount := v_opening_amount + v_in_amount - v_out_amount;

        IF v_closing_quantity = 0 AND v_closing_amount = 0 AND v_in_quantity = 0 AND v_out_quantity = 0 THEN
            CONTINUE;
        END IF;

        IF v_closing_quantity > 0 THEN
            v_average_cost := round(v_closing_amount / v_closing_quantity, 4);
        ELSE
            v_average_cost := 0;
        END IF;

        INSERT INTO inventory_month_snapshots (
            team_id, period, warehouse_id, product_id,
            opening_quantity, opening_amount, in_quantity, in_amount, out_quantity, out_amount,
            closing_quantity, closing_amount, average_cost, closed_by, closed_at, created_at, updated_at
        ) VALUES (
            p_team_id, p_period, v_pair.warehouse_id, v_pair.product_id,
            v_opening_quantity, v_opening_amount, v_in_quantity, v_in_amount, v_out_quantity, v_out_amount,
            v_closing_quantity, v_closing_amount, v_average_cost, p_user_id, now(), now(), now()
        );
    END LOOP;
END;
$$
SQL);
    }
};
