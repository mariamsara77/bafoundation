"use client";

import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { useEffect, useState } from "react";
import { ApiError, getDonationPaymentStatus, type Donation } from "@/lib/api";

export default function DonationSuccessPage() {
  const params = useSearchParams();
  const tranId = params.get("tran_id");
  const [donation, setDonation] = useState<Donation | null>(null);
  const [checking, setChecking] = useState(Boolean(tranId));

  useEffect(() => {
    if (!tranId) {
      setChecking(false);
      return;
    }

    getDonationPaymentStatus(tranId)
      .then(setDonation)
      .catch(() => setDonation(null))
      .finally(() => setChecking(false));
  }, [tranId]);

  const review = donation?.status === "review";
  const title = review ? "পেমেন্ট গ্রহণ করা হয়েছে" : "অনুদান সফল হয়েছে";
  const description = review
    ? "পেমেন্টটি নিরাপত্তা যাচাইয়ের জন্য পর্যালোচনায় আছে। যাচাই শেষ হলে অনুদানটি সম্পন্ন হিসেবে চিহ্নিত হবে।"
    : "আপনার পেমেন্ট যাচাই করে অনুদানটি আমাদের সিস্টেমে সংরক্ষণ করা হয়েছে।";

  return (
    <main className="mx-auto max-w-2xl px-4 py-20 sm:px-6">
      <div className="rounded-3xl bg-white p-8 text-center shadow-sm ring-1 ring-zinc-200 sm:p-12">
        <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-orange-50 text-2xl text-orange-600">
          {review ? "!" : "✓"}
        </div>
        <h1 className="mt-6 text-3xl font-bold">{checking ? "পেমেন্ট যাচাই হচ্ছে..." : title}</h1>
        <p className="mt-3 leading-7 text-zinc-500">{checking ? "আপনার লেনদেনের চূড়ান্ত অবস্থা যাচাই করছি।" : description}</p>

        {donation && (
          <div className="mt-7 rounded-2xl bg-zinc-50 p-5 text-left text-sm">
            <div className="flex justify-between gap-4 border-b border-zinc-200 pb-3">
              <span className="text-zinc-500">পরিমাণ</span>
              <strong>৳{Number(donation.amount).toLocaleString("en-US")}</strong>
            </div>
            <div className="flex justify-between gap-4 border-b border-zinc-200 py-3">
              <span className="text-zinc-500">বিভাগ</span>
              <strong>{donation.category?.name || "—"}</strong>
            </div>
            <div className="flex justify-between gap-4 pt-3">
              <span className="text-zinc-500">ট্রানজেকশন</span>
              <strong className="break-all text-right">{donation.tran_id}</strong>
            </div>
          </div>
        )}

        <div className="mt-7 flex flex-wrap justify-center gap-3">
          <Link href="/donate" className="rounded-2xl bg-orange-600 px-5 py-3 font-semibold text-white hover:bg-orange-700">আবার দান করুন</Link>
          <Link href="/" className="rounded-2xl border border-zinc-200 px-5 py-3 font-semibold text-zinc-700 hover:bg-zinc-50">হোমে ফিরুন</Link>
        </div>
      </div>
    </main>
  );
}
