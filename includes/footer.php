</main> <style>
    .site-footer {
        position: fixed;      /* ลอยติดหน้าจอ */
        bottom: 10px;         /* ห่างจากขอบล่าง 10px */
        right: 20px;          /* ชิดขวา (หรือ left:0; text-align:center; ถ้าอยากให้อยู่ตรงกลาง) */
        width: auto;          /* ขนาดตามตัวอักษร */
        z-index: 100;         /* อยู่ชั้นบน */
        pointer-events: none; /* ให้เมาส์คลิกทะลุได้ (กันไปบังปุ่มอื่น) */
    }
    
    .site-footer p {
        margin: 0;
        font-size: 0.75rem;   /* ตัวเล็กหน่อย */
        color: #cbd5e1;       /* สีจางๆ สบายตา */
        text-shadow: 0 1px 2px rgba(0,0,0,0.1); /* เงาเล็กน้อยให้อ่านออก */
    }
</style>

<footer class="site-footer">
    <div class="container-fluid"> <p>&copy; <?php echo date('Y'); ?> สำนักส่งเสริมการศึกษาทางไกลฯ - DLICT</p>
    </div>
</footer>

</body>
</html>