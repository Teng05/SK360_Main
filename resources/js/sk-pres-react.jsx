import React, { useEffect, useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

async function requestJson(url, options = {}) {
    const response = await fetch(url, {
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            ...(options.headers || {}),
        },
        ...options,
    });

    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
        const message = payload.message || Object.values(payload.errors || {}).flat().join(' ') || 'Request failed.';
        throw new Error(message);
    }

    return payload;
}

function useLiveData(url, interval = 5000) {
    const [data, setData] = useState(null);
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(true);

    const load = async () => {
        try {
            setError('');
            setData(await requestJson(url));
        } catch (error) {
            setError(error.message);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        load();
        const timer = window.setInterval(load, interval);
        return () => window.clearInterval(timer);
    }, [url, interval]);

    return { data, setData, error, loading, reload: load };
}

function LiveStatus({ loading, error, updatedAt }) {
    if (!error) {
        return null;
    }

    return (
        <div className="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-xs">
            <span className="font-bold text-red-700">Live update error</span>
            <span className="text-red-600">{error}</span>
        </div>
    );
}

function RankingsLive({ url }) {
    const { data, error, loading } = useLiveData(url);
    const leaderboard = data?.leaderboard || [];
    const pointSystem = data?.pointSystem || [];

    return (
        <section className="mb-8">
            <LiveStatus loading={loading} error={error} updatedAt={data?.updatedAt} />
            <div className="grid grid-cols-1 gap-6 xl:grid-cols-[1.5fr_1fr]">
                <div className="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <h3 className="mb-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Live Leaderboard</h3>
                    <div className="space-y-3">
                        {leaderboard.length === 0 ? (
                            <p className="text-sm text-gray-400">No rankings found.</p>
                        ) : leaderboard.map((row) => (
                            <div key={row.barangay_id} className="flex items-center gap-4 rounded-2xl border border-gray-100 bg-gray-50 px-4 py-3">
                                <div className="flex h-9 w-9 items-center justify-center rounded-full bg-red-600 text-xs font-black text-white">#{row.rank}</div>
                                <div className="min-w-0 flex-1">
                                    <div className="flex justify-between gap-3">
                                        <p className="truncate text-xs font-black uppercase text-gray-800">{row.name}</p>
                                        <p className="text-xs font-black text-red-600">{row.points} pts</p>
                                    </div>
                                    <div className="mt-2 grid grid-cols-3 gap-2 text-[10px] text-gray-500">
                                        <span>On-time {row.on_time}%</span>
                                        <span>Complete {row.completion}%</span>
                                        <span>Engage {row.engagement}%</span>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
                <div className="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <h3 className="mb-1 text-sm font-black text-gray-800">Points System</h3>
                    <p className="mb-5 text-xs text-gray-400">How points are earned and deducted</p>
                    <div className="space-y-3">
                        {pointSystem.map((rule) => {
                            const positive = rule.type === 'positive';
                            return (
                                <div key={rule.label} className="flex items-center justify-between gap-3 text-[11px]">
                                    <span className="font-medium text-gray-700">{rule.label}</span>
                                    <span className={`rounded-lg px-2 py-1 font-black ${positive ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'}`}>
                                        {rule.points > 0 ? '+' : ''}{rule.points} points
                                    </span>
                                </div>
                            );
                        })}
                    </div>
                </div>
            </div>
        </section>
    );
}

function ModuleLive({ url, storeUrl, deleteBaseUrl }) {
    const { data, setData, error, loading, reload } = useLiveData(url);
    const slots = data?.slots || [];
    const summary = data?.summary || {};

    return (
        <section className="mb-10">
            <LiveStatus loading={loading} error={error} updatedAt={data?.updatedAt} />
            <div className="mb-5 grid grid-cols-1 gap-5 md:grid-cols-4">
                {[
                    ['Total Slots', summary.totalSlots || 0],
                    ['Open Slots', summary.openSlots || 0],
                    ['Closed Slots', summary.closedSlots || 0],
                    ['All-Time Total', summary.allTimeTotal || 0],
                ].map(([label, value]) => (
                    <div key={label} className="rounded-2xl border border-red-100 bg-white p-5">
                        <p className="mb-2 text-sm text-gray-500">{label}</p>
                        <h3 className="text-3xl font-black text-gray-900">{value}</h3>
                    </div>
                ))}
            </div>
        </section>
    );
}

function CalendarLive({ url, storeUrl }) {
    const { data, setData, error, loading } = useLiveData(url);
    const upcoming = data?.upcomingEvents || [];

    return (
        <section className="mb-8">
            <LiveStatus loading={loading} error={error} updatedAt={data?.updatedAt} />
            <div className="rounded-2xl border border-gray-100 bg-white p-5">
                <h3 className="mb-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Live Upcoming Events</h3>
                <div className="space-y-3">
                    {upcoming.length === 0 ? <p className="text-sm text-gray-400">No upcoming events yet.</p> : upcoming.map((event) => (
                        <div key={event.id} className="flex items-center justify-between rounded-xl border border-gray-100 px-4 py-3">
                            <div>
                                <p className="text-sm font-bold text-gray-800">{event.title}</p>
                                <p className="text-xs text-gray-400">{event.start}</p>
                            </div>
                            <span className="text-xs text-gray-500">{String(event.type || 'other').replaceAll('_', ' ')}</span>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}

function mount(id, Component) {
    const element = document.getElementById(id);
    if (!element) return;

    createRoot(element).render(<Component {...element.dataset} />);
}

mount('react-rankings-live', RankingsLive);
mount('react-module-live', ModuleLive);
mount('react-calendar-live', CalendarLive);
