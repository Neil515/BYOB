# -*- coding: utf-8 -*-
import tkinter as tk
from tkinter import ttk, messagebox, filedialog
import subprocess
import os
import threading
import sys
from datetime import datetime
import locale

# Set locale for Chinese support
try:
    locale.setlocale(locale.LC_ALL, 'zh_TW.UTF-8')
except:
    try:
        locale.setlocale(locale.LC_ALL, 'Chinese_Taiwan.950')
    except:
        pass

class RestaurantCrawlerGUI:
    def __init__(self, root):
        self.root = root
        self.root.title("BYOB 餐廳爬蟲 - 圖形介面")
        self.root.geometry("600x500")
        self.root.resizable(False, False)
        
        # Set style
        style = ttk.Style()
        style.theme_use('clam')
        
        self.setup_ui()
        
    def setup_ui(self):
        # Title
        title_frame = ttk.Frame(self.root)
        title_frame.pack(fill="x", padx=20, pady=20)
        
        title_label = ttk.Label(title_frame, text="BYOB 餐廳爬蟲", 
                               font=("Microsoft JhengHei", 20, "bold"))
        title_label.pack()
        
        subtitle_label = ttk.Label(title_frame, text="Google Places API 爬蟲工具", 
                                  font=("Microsoft JhengHei", 12))
        subtitle_label.pack()
        
        # Settings area
        settings_frame = ttk.LabelFrame(self.root, text="搜尋設定", padding=20)
        settings_frame.pack(fill="x", padx=20, pady=10)
        
        # Search keyword
        ttk.Label(settings_frame, text="搜尋關鍵字:", font=("Microsoft JhengHei", 10)).grid(row=0, column=0, sticky="w", pady=5)
        self.search_var = tk.StringVar(value="台北 義式餐廳")
        search_entry = ttk.Entry(settings_frame, textvariable=self.search_var, width=40, font=("Microsoft JhengHei", 10))
        search_entry.grid(row=0, column=1, padx=10, pady=5)
        
        # Results count
        ttk.Label(settings_frame, text="結果數量:", font=("Microsoft JhengHei", 10)).grid(row=1, column=0, sticky="w", pady=5)
        self.max_results_var = tk.StringVar(value="20")
        results_entry = ttk.Entry(settings_frame, textvariable=self.max_results_var, width=10, font=("Microsoft JhengHei", 10))
        results_entry.grid(row=1, column=1, sticky="w", padx=10, pady=5)
        
        # Output file
        ttk.Label(settings_frame, text="輸出檔案:", font=("Microsoft JhengHei", 10)).grid(row=2, column=0, sticky="w", pady=5)
        self.output_var = tk.StringVar(value="restaurant_data.xlsx")
        output_entry = ttk.Entry(settings_frame, textvariable=self.output_var, width=30, font=("Microsoft JhengHei", 10))
        output_entry.grid(row=2, column=1, padx=10, pady=5)
        
        browse_btn = ttk.Button(settings_frame, text="瀏覽", command=self.browse_file)
        browse_btn.grid(row=2, column=2, padx=5, pady=5)
        
        # Auto naming option
        self.auto_naming_var = tk.BooleanVar(value=True)
        auto_naming_check = ttk.Checkbutton(settings_frame, text="自動生成唯一檔名 (包含時間戳記)", 
                                          variable=self.auto_naming_var)
        auto_naming_check.grid(row=3, column=1, columnspan=2, sticky="w", padx=10, pady=5)
        
        # Control buttons
        button_frame = ttk.Frame(self.root)
        button_frame.pack(fill="x", padx=20, pady=20)
        
        self.start_btn = ttk.Button(button_frame, text="開始爬取", 
                                   command=self.start_crawling, style="Accent.TButton")
        self.start_btn.pack(side="left", padx=5)
        
        self.stop_btn = ttk.Button(button_frame, text="停止", 
                                  command=self.stop_crawling, state="disabled")
        self.stop_btn.pack(side="left", padx=5)
        
        ttk.Button(button_frame, text="開啟結果檔案", 
                  command=self.open_result_file).pack(side="right", padx=5)
        
        # Progress bar
        self.progress = ttk.Progressbar(self.root, mode='indeterminate')
        self.progress.pack(fill="x", padx=20, pady=10)
        
        # Log area
        log_frame = ttk.LabelFrame(self.root, text="執行日誌", padding=10)
        log_frame.pack(fill="both", expand=True, padx=20, pady=10)
        
        # Create text area and scrollbar
        text_frame = ttk.Frame(log_frame)
        text_frame.pack(fill="both", expand=True)
        
        self.log_text = tk.Text(text_frame, height=10, wrap="word", font=("Microsoft JhengHei", 9))
        scrollbar = ttk.Scrollbar(text_frame, orient="vertical", command=self.log_text.yview)
        self.log_text.configure(yscrollcommand=scrollbar.set)
        
        self.log_text.pack(side="left", fill="both", expand=True)
        scrollbar.pack(side="right", fill="y")
        
        # Status bar
        self.status_var = tk.StringVar(value="就緒")
        status_bar = ttk.Label(self.root, textvariable=self.status_var, relief="sunken")
        status_bar.pack(fill="x", side="bottom")
        
    def browse_file(self):
        filename = filedialog.asksaveasfilename(
            defaultextension=".xlsx",
            filetypes=[("Excel files", "*.xlsx"), ("All files", "*.*")]
        )
        if filename:
            self.output_var.set(filename)
    
    def generate_unique_filename(self, base_filename, query):
        """
        Generate unique filename to avoid duplicates
        """
        # Get current time
        now = datetime.now()
        timestamp = now.strftime("%Y%m%d_%H%M%S")
        
        # Clean query string, remove special characters
        clean_query = query.replace(" ", "_").replace("/", "_").replace("\\", "_")
        clean_query = "".join(c for c in clean_query if c.isalnum() or c in "_-")
        
        # Split filename and extension
        if "." in base_filename:
            name, ext = base_filename.rsplit(".", 1)
        else:
            name = base_filename
            ext = "xlsx"
        
        # Generate new filename
        new_filename = f"{name}_{clean_query}_{timestamp}.{ext}"
        
        # Check if file exists, add number if exists
        counter = 1
        original_filename = new_filename
        while os.path.exists(new_filename):
            if "." in original_filename:
                name_part, ext_part = original_filename.rsplit(".", 1)
                new_filename = f"{name_part}_{counter:03d}.{ext_part}"
            else:
                new_filename = f"{original_filename}_{counter:03d}"
            counter += 1
        
        return new_filename
    
    def log_message(self, message):
        self.log_text.insert(tk.END, message + "\n")
        self.log_text.see(tk.END)
        self.root.update()
    
    def start_crawling(self):
        # Check required files
        if not os.path.exists("restaurant_crawler.py"):
            messagebox.showerror("錯誤", "找不到 restaurant_crawler.py 檔案")
            return
        
        if not os.path.exists("config.py"):
            messagebox.showerror("錯誤", "找不到 config.py 檔案")
            return
        
        # Update settings
        self.update_config()
        
        # Update UI state
        self.start_btn.config(state="disabled")
        self.stop_btn.config(state="normal")
        self.progress.start()
        self.status_var.set("正在執行爬蟲...")
        
        # Clear log
        self.log_text.delete(1.0, tk.END)
        
        # Run crawler in new thread
        self.crawling_thread = threading.Thread(target=self.run_crawler)
        self.crawling_thread.daemon = True
        self.crawling_thread.start()
    
    def update_config(self):
        # Read config.py and update settings
        try:
            with open("config.py", "r", encoding="utf-8") as f:
                content = f.read()
        except UnicodeDecodeError:
            # Try with different encoding
            try:
                with open("config.py", "r", encoding="cp950") as f:
                    content = f.read()
            except:
                with open("config.py", "r", encoding="big5") as f:
                    content = f.read()
        
        # Update search keyword - handle any previous search query
        import re
        content = re.sub(r'SEARCH_QUERY = "[^"]*"', f'SEARCH_QUERY = "{self.search_var.get()}"', content)
        
        # Update results count
        content = content.replace(
            f'MAX_RESULTS = 20',
            f'MAX_RESULTS = {self.max_results_var.get()}'
        )
        
        # Handle output filename
        output_filename = self.output_var.get()
        if self.auto_naming_var.get():
            # If auto naming enabled, generate unique filename
            output_filename = self.generate_unique_filename(output_filename, self.search_var.get())
        
        # Update output file - handle any previous output file
        content = re.sub(r'OUTPUT_FILE = "[^"]*"', f'OUTPUT_FILE = "{output_filename}"', content)
        
        # Write back to file
        with open("config.py", "w", encoding="utf-8") as f:
            f.write(content)
        
        # Update filename display in GUI
        if self.auto_naming_var.get():
            self.output_var.set(output_filename)
    
    def run_crawler(self):
        try:
            # Execute crawler program
            process = subprocess.Popen(
                [sys.executable, "restaurant_crawler.py"],
                stdout=subprocess.PIPE,
                stderr=subprocess.STDOUT,
                text=True,
                encoding='utf-8',
                errors='replace'
            )
            
            # Display output in real-time
            for line in process.stdout:
                self.log_message(line.strip())
            
            process.wait()
            
            if process.returncode == 0:
                self.log_message("\n✓ 爬蟲執行完成！")
                self.status_var.set("執行完成")
                messagebox.showinfo("完成", f"爬蟲執行完成！\n結果已儲存到: {self.output_var.get()}")
            else:
                self.log_message(f"\n✗ 程式執行失敗，返回碼: {process.returncode}")
                self.status_var.set("執行失敗")
                messagebox.showerror("錯誤", "爬蟲執行失敗，請檢查日誌")
                
        except Exception as e:
            self.log_message(f"\n✗ 執行錯誤: {str(e)}")
            self.status_var.set("執行錯誤")
            messagebox.showerror("錯誤", f"執行時發生錯誤:\n{str(e)}")
        finally:
            # Restore UI state
            self.start_btn.config(state="normal")
            self.stop_btn.config(state="disabled")
            self.progress.stop()
    
    def stop_crawling(self):
        # Implement stop functionality here
        self.status_var.set("已停止")
        messagebox.showinfo("停止", "爬蟲已停止")
    
    def open_result_file(self):
        filename = self.output_var.get()
        if os.path.exists(filename):
            os.startfile(filename)
        else:
            messagebox.showwarning("警告", f"檔案不存在: {filename}")

def main():
    root = tk.Tk()
    app = RestaurantCrawlerGUI(root)
    root.mainloop()

if __name__ == "__main__":
    main()
