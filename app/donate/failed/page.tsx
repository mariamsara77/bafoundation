import Link from "next/link";

export default function DonationFailedPage() {
  return (
    <main className="mx-auto max-w-2xl px-4 py-24 text-center sm:px-6">
      <div className="rounded-3xl bg-white p-8 shadow-sm ring-1 ring-zinc-200 sm:p-12">
        <h1 className="text-3xl font-bold">পেমেন্ট সম্পন্ন হয়নি</h1>
        <p className="mt-3 leading-7 text-zinc-500">লেনদেনটি সফলভাবে সম্পন্ন হয়নি। চাইলে আবার চেষ্টা করতে পারেন।</p>
        <Link href="/donate" className="mt-7 inline-flex rounded-2xl bg-orange-600 px-5 py-3 font-semibold text-white hover:bg-orange-700">আবার দান করুন</Link>
      </div>
    </main>
  );
}