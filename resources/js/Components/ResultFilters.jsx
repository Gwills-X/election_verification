import { router } from "@inertiajs/react";
import { useState } from "react";

const input = "rounded-md border-gray-300 text-sm shadow-sm";

export default function ResultFilters({
    routeName,
    filters = {},
    fields = [],
    options = {},
}) {
    const keys = [
        "q",
        "election_id",
        "state",
        "lga",
        "ward",
        "status",
        "review",
    ];
    const [v, setV] = useState(
        Object.fromEntries(keys.map((k) => [k, filters[k] ?? ""])),
    );
    const set = (key) => (event) => {
        const value = event.target.value;
        setV((current) => {
            const next = { ...current, [key]: value };

            if (key === "election_id") {
                next.state = "";
                next.lga = "";
                next.ward = "";
            } else if (key === "state") {
                next.lga = "";
                next.ward = "";
            } else if (key === "lga") {
                next.ward = "";
            }

            return next;
        });
    };
    const submit = (e) => {
        e.preventDefault();
        router.get(route(routeName), v, { preserveState: true });
    };
    const reset = () => {
        setV(Object.fromEntries(keys.map((k) => [k, ""])));
        router.get(route(routeName));
    };
    return (
        <form
            onSubmit={submit}
            className="mb-4 grid gap-3 rounded-lg border bg-white p-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8"
        >
            {fields.includes("q") && (
                <input
                    className={input}
                    placeholder="PU code or name"
                    value={v.q}
                    onChange={set("q")}
                />
            )}
            {fields.includes("election_id") && (
                <select
                    className={input}
                    value={v.election_id}
                    onChange={set("election_id")}
                >
                    <option value="">All elections</option>
                    {(options.elections ?? []).map((e) => (
                        <option key={e.id} value={e.id}>
                            {e.name}
                        </option>
                    ))}
                </select>
            )}
            {fields.includes("state") && (
                <select
                    className={input}
                    value={v.state}
                    onChange={set("state")}
                >
                    <option value="">All states</option>
                    {(options.states ?? []).map((s) => (
                        <option key={s}>{s}</option>
                    ))}
                </select>
            )}
            {fields.includes("lga") && (
                <select className={input} value={v.lga} onChange={set("lga")}>
                    <option value="">All LGAs</option>
                    {(options.lgas ?? []).map((s) => (
                        <option key={s}>{s}</option>
                    ))}
                </select>
            )}
            {fields.includes("ward") && (
                <select className={input} value={v.ward} onChange={set("ward")}>
                    <option value="">All wards</option>
                    {(options.wards ?? []).map((s) => (
                        <option key={s}>{s}</option>
                    ))}
                </select>
            )}
            {fields.includes("status") && (
                <select
                    className={input}
                    value={v.status}
                    onChange={set("status")}
                >
                    <option value="">All statuses</option>
                    <option value="VALID">Valid</option>
                    <option value="INVALID">Invalid</option>
                    <option value="FLAGGED_FOR_REVIEW">
                        Flagged for review
                    </option>
                    <option value="PENDING">Pending</option>
                </select>
            )}
            {fields.includes("review") && (
                <select
                    className={input}
                    value={v.review}
                    onChange={set("review")}
                >
                    <option value="">Any review state</option>
                    <option value="reviewed">Reviewed</option>
                    <option value="unreviewed">Not reviewed</option>
                </select>
            )}
            <div className="flex gap-2">
                <button className="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white">
                    Apply
                </button>
                <button
                    type="button"
                    onClick={reset}
                    className="rounded-md border px-4 py-2 text-sm"
                >
                    Reset
                </button>
            </div>
        </form>
    );
}
