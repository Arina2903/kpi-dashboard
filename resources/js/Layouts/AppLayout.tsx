import { ReactNode } from 'react';
import Sidebar from '../Components/Sidebar';
import TopBar from '../Components/TopBar';
import AniraChatWidget from '../Components/AniraChatWidget';
import { SidebarProvider, useSidebar } from './SidebarContext';

function MainContent({ children }: { children: ReactNode }) {
    const { collapsed } = useSidebar();

    return (
        <main
            id="mainContent"
            className={`min-h-screen transition-all duration-300 bg-[#F5F5F3] ${collapsed ? 'ml-[64px]' : 'ml-[230px]'}`}
        >
            {children}
        </main>
    );
}

export default function AppLayout({ children }: { children: ReactNode }) {
    return (
        <SidebarProvider>
            <div className="min-h-screen bg-[#F5F5F3]">
                <AniraChatWidget />
                <TopBar />
                <Sidebar />
                <MainContent>{children}</MainContent>
            </div>
        </SidebarProvider>
    );
}
