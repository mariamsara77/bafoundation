"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useEffect, useState } from "react";
import AuthButton from "@/components/AuthButton";

const links = [
  { href:"/", label:"হোম" },
  { href:"/about", label:"আমাদের সম্পর্কে" },
  { href:"/activities", label:"কার্যক্রম" },
  { href:"/works", label:"কাজের প্রস্তাব" },
  { href:"/how-it-works", label:"কীভাবে কাজ করে" },
  { href:"/members", label:"সদস্যবৃন্দ" },
  { href:"/contact", label:"যোগাযোগ" },
  { href:"/donate", label:"দান করুন" },
];

export default function Navbar() {
  const pathname = usePathname();
  const [open,setOpen] = useState(false);
  const [installGuide,setInstallGuide] = useState(false);
  const [isInstalled,setIsInstalled] = useState(false);

  useEffect(() => {
    const media = window.matchMedia("(display-mode: standalone)");
    const syncInstalled = () => setIsInstalled(media.matches);
    syncInstalled();
    media.addEventListener?.("change", syncInstalled);
    const onInstalled = () => setIsInstalled(true);
    const onGuide = () => setInstallGuide(true);
    window.addEventListener("bafoundation:pwa-installed", onInstalled);
    window.addEventListener("bafoundation:pwa-guide", onGuide);
    return () => {
      media.removeEventListener?.("change", syncInstalled);
      window.removeEventListener("bafoundation:pwa-installed", onInstalled);
      window.removeEventListener("bafoundation:pwa-guide", onGuide);
    };
  }, []);

  const requestInstall = () => {
    window.dispatchEvent(new CustomEvent("bafoundation:pwa-install-request"));
  };
  return <header className="sticky top-0 z-50 border-b border-zinc-200/80 bg-white/95 backdrop-blur">
    <div className="mx-auto flex min-h-16 max-w-7xl items-center justify-between gap-3 py-2 px-4 sm:px-6 lg:px-8">
      <Link href="/" className="flex min-w-0 items-center gap-3" onClick={()=>setOpen(false)}>
        <span className="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-orange-50"><img src="/500.png" alt="" className="h-9 w-9 object-contain" /></span>
        <span className="min-w-0"><span className="block text-sm font-bold leading-5 text-orange-600 sm:text-base">বন্ধু আদর্শ ফাউন্ডেশন</span></span>
      </Link>
      <nav className="hidden items-center gap-1 lg:flex" aria-label="প্রধান নেভিগেশন">{links.map(link=>{const active=link.href==="/" ? pathname==="/" : pathname.startsWith(link.href); return <Link key={link.href} href={link.href} className={`rounded-xl px-3.5 py-2 text-sm font-semibold transition ${active?"bg-orange-50 text-orange-700":"text-zinc-600 hover:bg-zinc-50 hover:text-zinc-950"}`}>{link.label}</Link>})}</nav>
      <div className="flex items-center gap-2">
        {!isInstalled && (
          <button type="button" onClick={requestInstall} className="hidden rounded-xl bg-orange-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-orange-700 sm:inline-flex">
            অ্যাপ ইনস্টল
          </button>
        )}
        <div className="hidden lg:flex"><AuthButton /></div>
        <button aria-label={open?"মেনু বন্ধ করুন":"মেনু খুলুন"} aria-expanded={open} onClick={()=>setOpen(v=>!v)} className="rounded-xl border border-zinc-200 p-2.5 text-zinc-700 lg:hidden">{open?"×":"☰"}</button>
        <div className="lg:hidden"><AuthButton /></div>
      </div>
    </div>
    {installGuide && (
      <div className="fixed inset-0 z-[80] flex items-center justify-center bg-zinc-950/40 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="pwa-guide-title">
        <div className="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
          <div className="flex items-start justify-between gap-4">
            <div>
              <p className="text-sm font-bold text-orange-700">অ্যাপ ইনস্টল</p>
              <h2 id="pwa-guide-title" className="mt-1 text-xl font-bold text-zinc-950">এই browser-এ সরাসরি prompt পাওয়া যাচ্ছে না</h2>
            </div>
            <button type="button" onClick={() => setInstallGuide(false)} className="rounded-full px-2 py-1 text-xl text-zinc-400 hover:bg-zinc-100" aria-label="বন্ধ করুন">×</button>
          </div>
          <div className="mt-5 rounded-2xl bg-zinc-50 p-4 text-sm leading-7 text-zinc-600">
            Chrome বা Edge-এর মতো supported browser-এ এই সাইট খুলে browser menu থেকে <strong>Add to Home Screen</strong> / <strong>Install app</strong> নির্বাচন করুন।
          </div>
          <button type="button" onClick={() => setInstallGuide(false)} className="mt-5 w-full rounded-2xl bg-orange-600 px-5 py-3 font-bold text-white hover:bg-orange-700">ঠিক আছে</button>
        </div>
      </div>
    )}
    {open && <div className="border-t border-zinc-100 bg-white px-4 pb-4 pt-2 lg:hidden">
    </div>
    {open && <div className="border-t border-zinc-100 bg-white px-4 pb-4 pt-2 lg:hidden"><nav className="space-y-1">{links.map(link=><Link key={link.href} href={link.href} onClick={()=>setOpen(false)} className="block rounded-xl px-3 py-3 text-sm font-semibold text-zinc-700 hover:bg-zinc-50">{link.label}</Link>)}</nav></div>}
  </header>;
}
