import Link from "next/link";

export default function DonationSuccessPage() {
  return (
    <main className="mx-auto max-w-2xl px-4 py-24 text-center sm:px-6">
      <div className="rounded-3xl bg-white p-8 shadow-sm ring-1 ring-zinc-200 sm:p-12">
        <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-orange-50 text-2xl text-orange-600">✓</div>
        <h1 className="mt-6 text-3xl font-bold">অনুদান সফল হয়েছে</h1>
        <p className="mt-3 leading-7 text-zinc-500">আপনার পেমেন্ট যাচাই করে অনুদানটি আমাদের সিস্টেমে সংরক্ষণ করা হয়েছে।</p>
        <Link href="/" className="mt-7 inline-flex rounded-2xl bg-orange-600 px-5 py-3 font-semibold text-white hover:bg-orange-700">হোমে ফিরুন</Link>
      </div>
    </main>
  );
}