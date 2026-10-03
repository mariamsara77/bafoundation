"use client";

export default function OfflinePage() {
  return (
    <main className="flex min-h-[calc(100vh-8rem)] items-center justify-center px-5 py-16">
      <section className="w-full max-w-lg rounded-3xl border border-zinc-200 bg-white p-8 text-center shadow-sm sm:p-10">
        <div className="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-orange-50 text-3xl text-orange-600">
          ☁
        </div>
        <h1 className="text-2xl font-bold text-zinc-950 sm:text-3xl">আপনি বর্তমানে অফলাইনে আছেন</h1>
        <p className="mt-3 text-sm leading-7 text-zinc-600 sm:text-base">
          ইন্টারনেট সংযোগ ফিরে এলে পেজটি রিফ্রেশ করুন। আগে থেকে খোলা বন্ধু আদর্শ ফাউন্ডেশন-এর কিছু
          কনটেন্ট অফলাইনেও ব্যবহার করা যেতে পারে।
        </p>
        <button
          type="button"
          onClick={() => window.location.reload()}
          className="mt-7 rounded-full bg-orange-600 px-6 py-3 text-sm font-bold text-white transition hover:bg-orange-700"
        >
          আবার চেষ্টা করুন
        </button>
      </section>
    </main>
  );
}
