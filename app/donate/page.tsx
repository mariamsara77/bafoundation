"use client";

import { FormEvent, useEffect, useMemo, useState } from "react";
import { ApiError, getDonationCategories, getProfile, startDonation, type DonationCategory } from "@/lib/api";
import { useAuth } from "@/components/AuthProvider";

const quickAmounts = [500, 1000, 2000, 5000];

export default function DonatePage() {
  const { user } = useAuth();
  const [categories, setCategories] = useState<DonationCategory[]>([]);
  const [categoryId, setCategoryId] = useState("");
  const [amount, setAmount] = useState("1000");
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [phone, setPhone] = useState("");
  const [message, setMessage] = useState("");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");

  useEffect(() => {
    getDonationCategories()
      .then((items) => {
        setCategories(items);
        if (items[0]) setCategoryId(String(items[0].id));
      })
      .catch(() => setError("অনুদানের বিভাগগুলো আনা যায়নি।"));
  }, []);

  const selectedCategory = useMemo(
    () => categories.find((item) => String(item.id) === categoryId),
    [categories, categoryId],
  );

  async function submit(event: FormEvent) {
    event.preventDefault();
    setError("");

    const numericAmount = Number(amount);
    if (!categoryId || !numericAmount || numericAmount < 10) {
      setError("বিভাগ নির্বাচন করুন এবং কমপক্ষে ৳১০ অনুদান দিন।");
      return;
    }
    if (!name.trim() || !phone.trim()) {
      setError("নাম ও মোবাইল নম্বর দিন।");
      return;
    }

    setBusy(true);
    try {
      const result = await startDonation({
        donation_category_id: Number(categoryId),
        amount: numericAmount,
        donor_name: name.trim(),
        donor_email: email.trim() || undefined,
        donor_phone: phone.trim(),
        message: message.trim() || undefined,
      });
      window.location.assign(result.checkout_url);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "পেমেন্ট শুরু করা যায়নি। আবার চেষ্টা করুন।");
      setBusy(false);
    }
  }

  return (
    <main className="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
      <div className="grid gap-8 lg:grid-cols-[.85fr_1.15fr] lg:items-start">
        <section className="rounded-3xl bg-zinc-950 p-7 text-white sm:p-10">
          <p className="text-sm font-semibold text-orange-300">অনুদান</p>
          <h1 className="mt-2 text-4xl font-bold leading-tight">আপনার সহায়তা একটি উদ্যোগকে এগিয়ে নিতে পারে</h1>
          <p className="mt-5 leading-8 text-zinc-300">
            আপনার পছন্দের বিভাগ নির্বাচন করে নিরাপদ অনলাইন পেমেন্টের মাধ্যমে অনুদান দিতে পারেন।
          </p>
          <div className="mt-8 space-y-3 text-sm text-zinc-200">
            <div className="rounded-2xl bg-white/10 p-4">মোবাইল ব্যাংকিং ও ব্যাংকভিত্তিক পেমেন্ট চ্যানেল</div>
            <div className="rounded-2xl bg-white/10 p-4">প্রতিটি লেনদেনের আলাদা ট্রানজেকশন রেকর্ড</div>
            <div className="rounded-2xl bg-white/10 p-4">পেমেন্ট যাচাইয়ের পরেই অনুদান সম্পন্ন হিসেবে সংরক্ষণ</div>
          </div>
        </section>

        <section className="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-zinc-200 sm:p-8">
          <div>
            <h2 className="text-2xl font-bold">অনুদানের তথ্য</h2>
            <p className="mt-1 text-sm text-zinc-500">তথ্য পূরণ করে পেমেন্ট পেজে যান।</p>
          </div>

          <form onSubmit={submit} className="mt-7 space-y-5">
            <label className="block">
              <span className="mb-2 block text-sm font-semibold">অনুদানের বিভাগ</span>
              <select value={categoryId} onChange={(e) => setCategoryId(e.target.value)} required className="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 outline-none focus:border-orange-500 focus:bg-white">
                <option value="">বিভাগ নির্বাচন করুন</option>
                {categories.map((category) => <option key={category.id} value={category.id}>{category.name}</option>)}
              </select>
              {selectedCategory?.description && <span className="mt-2 block text-xs leading-5 text-zinc-500">{selectedCategory.description}</span>}
            </label>

            <div>
              <span className="mb-2 block text-sm font-semibold">অনুদানের পরিমাণ</span>
              <div className="grid grid-cols-4 gap-2">
                {quickAmounts.map((value) => (
                  <button key={value} type="button" onClick={() => setAmount(String(value))} className={Number(amount) === value ? "rounded-xl bg-orange-600 px-2 py-2.5 text-sm font-semibold text-white" : "rounded-xl bg-zinc-100 px-2 py-2.5 text-sm font-semibold text-zinc-700 hover:bg-orange-50 hover:text-orange-700"}>
                    ৳{value.toLocaleString("en-US")}
                  </button>
                ))}
              </div>
              <input value={amount} onChange={(e) => setAmount(e.target.value.replace(/[^0-9.]/g, ""))} type="number" min="10" max="500000" step="0.01" required className="mt-3 w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 outline-none focus:border-orange-500 focus:bg-white" placeholder="পরিমাণ" />
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
              <Input label="নাম" value={name} onChange={setName} required placeholder="আপনার নাম" />
              <Input label="মোবাইল নম্বর" value={phone} onChange={setPhone} required placeholder="01XXXXXXXXX" />
            </div>
            <Input label="ইমেইল (ঐচ্ছিক)" value={email} onChange={setEmail} type="email" placeholder="আপনার ইমেইল" />
            <label className="block">
              <span className="mb-2 block text-sm font-semibold">বার্তা (ঐচ্ছিক)</span>
              <textarea value={message} onChange={(e) => setMessage(e.target.value)} rows={3} maxLength={1000} className="w-full resize-y rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 outline-none focus:border-orange-500 focus:bg-white" placeholder="কোনো বার্তা থাকলে লিখুন" />
            </label>

            {error && <div role="alert" className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{error}</div>}

            <button disabled={busy || categories.length === 0} className="w-full rounded-2xl bg-orange-600 px-5 py-3.5 font-bold text-white transition hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-60">
              {busy ? "পেমেন্ট পেজ প্রস্তুত হচ্ছে..." : "দান করুন"}
            </button>
          </form>
        </section>
      </div>
    </main>
  );
}

function Input({ label, value, onChange, type = "text", required = false, placeholder = "" }: { label: string; value: string; onChange: (value: string) => void; type?: string; required?: boolean; placeholder?: string }) {
  return (
    <label className="block">
      <span className="mb-2 block text-sm font-semibold">{label}</span>
      <input value={value} onChange={(e) => onChange(e.target.value)} type={type} required={required} className="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 outline-none focus:border-orange-500 focus:bg-white" placeholder={placeholder} />
    </label>
  );
}