'use client';
import { useEffect } from 'react';
import { useRouter } from 'next/navigation';

export default function NewQuotationPage() {
  const router = useRouter();
  useEffect(() => {
    router?.replace('/quotations/new-draft');
  }, [router]);
  return (
    <div className="flex items-center justify-center min-h-screen">
      <p className="text-muted-foreground text-sm">Creating new quotation...</p>
    </div>
  );
}
