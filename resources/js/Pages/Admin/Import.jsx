import AppLayout from "../../Components/AppLayout";
import InputError from "../../Components/InputError";
import InputLabel from "../../Components/InputLabel";
import PrimaryButton from "../../Components/PrimaryButton";
import { Link, useForm } from "@inertiajs/react";

const sel = "mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm";

const formatSummaryStatus = (status) => {
    switch (status) {
        case "ALREADY_IMPORTED":
            return "Already Imported";
        case "COMPLETED_WITH_CONFLICTS":
            return "Completed with conflicts";
        case "FAILED":
            return "Failed";
        default:
            return "Completed";
    }
};

const statusClasses = {
    ALREADY_IMPORTED: "border-emerald-200 bg-emerald-50 text-emerald-800",
    COMPLETED_WITH_CONFLICTS: "border-amber-200 bg-amber-50 text-amber-800",
    FAILED: "border-red-200 bg-red-50 text-red-800",
    COMPLETED: "border-slate-200 bg-slate-100 text-slate-700",
};

export default function Import({ elections, selectedElectionId, summary }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        election_id: selectedElectionId ?? elections[0]?.id ?? "",
        dataset_type: "REAL",
        election_result_type:
            elections.find(
                (e) =>
                    String(e.id) ===
                    String(selectedElectionId ?? elections[0]?.id),
            )?.election_type?.code ?? "",
        file: null,
    });
    const selectedElection = elections.find(
        (e) => String(e.id) === String(data.election_id),
    );
    const submit = (e) => {
        e.preventDefault();
        post(route("admin.import.store"), {
            forceFormData: true,
            onSuccess: () => reset("file"),
        });
    };

    const submitted = summary?.submitted ?? summary?.found ?? 0;
    const status =
        summary?.status ??
        (summary?.conflicts > 0
            ? "COMPLETED_WITH_CONFLICTS"
            : summary?.new_records === 0 && summary?.existing_records > 0
              ? "ALREADY_IMPORTED"
              : "COMPLETED");

    return (
        <AppLayout title="Import Election Dataset">
            <form
                onSubmit={submit}
                className="max-w-2xl space-y-4 rounded-lg border bg-white p-6 shadow-sm"
            >
                <div>
                    <InputLabel value="Election" />
                    <select
                        className={sel}
                        value={data.election_id}
                        onChange={(e) => {
                            const electionId = e.target.value;

                            const selected = elections.find(
                                (election) =>
                                    String(election.id) === String(electionId),
                            );

                            setData({
                                ...data,
                                election_id: electionId,
                                election_result_type:
                                    selected?.election_type?.code ?? "",
                            });
                        }}
                    >
                        {elections.map((e) => (
                            <option key={e.id} value={e.id}>
                                {e.name} ({e.status})
                            </option>
                        ))}
                    </select>
                    {selectedElection?.election_type?.code && (
                        <div className="mt-2 rounded-md border border-blue-200 bg-blue-50 px-3 py-2 text-sm text-blue-800">
                            <strong>Expected dataset:</strong>{" "}
                            {selectedElection.election_type.code} Election
                            results
                        </div>
                    )}
                    {elections.length === 0 && (
                        <p className="mt-1 text-xs text-red-600">
                            Create an election first.
                        </p>
                    )}
                    <InputError message={errors.election_id} />
                </div>

                <div>
                    <InputLabel value="Dataset type" />
                    <div className="mt-2 space-y-2 text-sm">
                        <label className="flex items-center gap-2">
                            <input
                                type="radio"
                                checked={data.dataset_type === "REAL"}
                                onChange={() => setData("dataset_type", "REAL")}
                            />{" "}
                            Real dataset (official figures)
                        </label>
                        <label className="flex items-center gap-2">
                            <input
                                type="radio"
                                checked={data.dataset_type === "SYNTHETIC"}
                                onChange={() =>
                                    setData("dataset_type", "SYNTHETIC")
                                }
                            />{" "}
                            Synthetic / test dataset (clearly labelled test
                            records such as TEST-001)
                        </label>
                    </div>
                    <InputError message={errors.dataset_type} />
                </div>
                <div>
                    <InputLabel value="CSV file" />
                    <input
                        type="file"
                        accept=".csv,.txt"
                        onChange={(e) => setData("file", e.target.files[0])}
                        className="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-slate-800 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white"
                    />
                    <p className="mt-1 text-xs text-gray-400">
                        Required columns: State, LGA, Ward, and PU-Code.
                        Candidate/party columns are detected automatically. The
                        uploaded dataset must match the selected election type,
                        year, and geographic scope. CSV, TSV, semicolon or pipe
                        separated.
                    </p>
                    <InputError message={errors.file} />
                </div>
                <PrimaryButton
                    disabled={
                        processing || elections.length === 0 || !data.file
                    }
                >
                    Upload &amp; Validate
                </PrimaryButton>
            </form>

            {summary && (
                <div className="mt-6 rounded-lg border bg-white p-6 shadow-sm">
                    <div className="mb-4 flex items-center justify-between gap-3">
                        <h3 className="font-semibold text-slate-800">
                            Import Summary
                        </h3>
                        <span
                            className={`inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold ${statusClasses[status] ?? statusClasses.COMPLETED}`}
                        >
                            {formatSummaryStatus(status)}
                        </span>
                    </div>

                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-7">
                        {[
                            ["Records submitted", submitted, "text-slate-700"],
                            [
                                "New records",
                                summary.new_records ?? 0,
                                "text-emerald-600",
                            ],
                            [
                                "Already existing",
                                summary.existing_records ?? 0,
                                "text-blue-600",
                            ],
                            [
                                "Conflicts",
                                summary.conflicts ?? 0,
                                "text-amber-600",
                            ],
                            [
                                "Invalid records",
                                summary.invalid ?? 0,
                                "text-red-600",
                            ],
                            [
                                "Skipped / malformed",
                                summary.skipped ?? 0,
                                "text-gray-600",
                            ],
                            [
                                "Overall status",
                                formatSummaryStatus(status),
                                "text-slate-700",
                            ],
                        ].map(([label, value, color]) => (
                            <div
                                key={label}
                                className="rounded-md border border-slate-200 bg-slate-50 p-3 text-center"
                            >
                                <p className={`text-xl font-bold ${color}`}>
                                    {value}
                                </p>
                                <p className="text-[10px] uppercase tracking-wide text-gray-500">
                                    {label}
                                </p>
                            </div>
                        ))}
                    </div>

                    <div className="mt-4 rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                        {status === "ALREADY_IMPORTED" ? (
                            <p>
                                <strong>Already Imported.</strong> These records
                                already exist with the same values and were not
                                re-created.
                            </p>
                        ) : (
                            <p>
                                {submitted} records submitted •{" "}
                                {summary.new_records ?? 0} new •{" "}
                                {summary.existing_records ?? 0} already existing
                                • {summary.conflicts ?? 0} conflicts •{" "}
                                {summary.invalid ?? 0} invalid
                            </p>
                        )}
                    </div>

                    {(summary.flagged ?? 0) > 0 && (
                        <p className="mt-3 text-sm text-amber-700">
                            {summary.flagged} records require follow-up review
                            before final approval.
                        </p>
                    )}

                    <div className="mt-4">
                        <p className="text-xs font-semibold uppercase text-gray-500">
                            Candidate / party columns detected
                        </p>
                        <div className="mt-1 flex flex-wrap gap-1">
                            {summary.candidate_columns.map((c) => (
                                <span
                                    key={c}
                                    className="rounded bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700"
                                >
                                    {c}
                                </span>
                            ))}
                            {summary.candidate_columns.length === 0 && (
                                <span className="text-xs text-gray-400">
                                    None detected
                                </span>
                            )}
                        </div>
                    </div>
                    {summary.rules_skipped.length > 0 && (
                        <div className="mt-4 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                            <p className="font-semibold">
                                Rules skipped (not applicable to this dataset):
                            </p>
                            <ul className="mt-1 list-inside list-disc">
                                {summary.rules_skipped.map((r) => (
                                    <li key={r}>{r}</li>
                                ))}
                            </ul>
                        </div>
                    )}
                    <Link
                        href={route("admin.flagged")}
                        className="mt-4 inline-block text-sm text-blue-700 hover:underline"
                    >
                        Review flagged records →
                    </Link>
                </div>
            )}
        </AppLayout>
    );
}
