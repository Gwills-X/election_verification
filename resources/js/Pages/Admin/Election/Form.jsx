import AppLayout from "../../../Components/AppLayout";
import InputError from "../../../Components/InputError";
import InputLabel from "../../../Components/InputLabel";
import PrimaryButton from "../../../Components/PrimaryButton";
import { Link, useForm } from "@inertiajs/react";

const lbl = "text-sm font-medium text-gray-700";

export default function Form({ election, electionTypes }) {
    const { data, setData, post, put, processing, errors } = useForm({
        election_type_id:
            election?.election_type_id ?? electionTypes[0]?.id ?? "",
        year: election?.year ?? new Date().getFullYear(),
    });
    const submit = (e) => {
        e.preventDefault();
        election
            ? put(route("admin.elections.update", election.id))
            : post(route("admin.elections.store"));
    };
    return (
        <AppLayout title={election ? "Edit Election" : "Create Election"}>
            <form
                onSubmit={submit}
                className="max-w-xl space-y-5 rounded-lg border bg-white p-6 shadow-sm"
            >
                <div>
                    <InputLabel value="Election type" className={lbl} />
                    <select
                        className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm"
                        value={data.election_type_id}
                        onChange={(e) =>
                            setData("election_type_id", e.target.value)
                        }
                    >
                        {electionTypes.map((type) => (
                            <option key={type.id} value={type.id}>
                                {type.name}
                            </option>
                        ))}
                    </select>
                    <InputError message={errors.election_type_id} />
                </div>
                <div>
                    <InputLabel value="Year" className={lbl} />
                    <input
                        type="number"
                        min="1998"
                        max="2100"
                        className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm"
                        value={data.year}
                        onChange={(e) => setData("year", e.target.value)}
                    />
                    <InputError message={errors.year} />
                </div>
                <div className="flex gap-3">
                    <PrimaryButton disabled={processing}>
                        {election ? "Save changes" : "Create election"}
                    </PrimaryButton>
                    <Link
                        href={route("admin.elections.index")}
                        className="rounded-md border px-4 py-2 text-sm"
                    >
                        Cancel
                    </Link>
                </div>
            </form>
        </AppLayout>
    );
}
