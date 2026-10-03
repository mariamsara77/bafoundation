import Link from "next/link";

export default function NotFound() {
  return (
    <main className="flex min-h-[calc(100vh-8rem)] items-center justify-center px-4 py-16">
      <section className="w-full max-w-xl rounded-3xl bg-white p-8 text-center shadow-sm ring-1 ring-zinc-200 sm:p-10">
        <p className="text-sm font-bold text-orange-700">404</p>
        <h1 className="mt-2 text-3xl font-bold text-zinc-950 sm:text-4xl">পাতাটি পাওয়া যায়নি</h1>
        <p className="mt-3 leading-7 text-zinc-500">
          আপনি যে পাতাটি খুঁজছেন সেটি সরানো হয়েছে, ঠিকানা পরিবর্তন হয়েছে অথবা আর উপলব্ধ নেই।
        </p>
        <div className="mt-7 flex flex-wrap justify-center gap-3">
          <Link href="/" className="rounded-full bg-orange-600 px-5 py-3 font-bold text-white transition hover:bg-orange-700">
            হোমে ফিরে যান
          </Link>
          <Link href="/contact" className="rounded-full border border-zinc-200 px-5 py-3 font-bold text-zinc-700 transition hover:bg-zinc-50">
            যোগাযোগ করুন
          </Link>
        </div>
      </section>
    </main>
  );
}
