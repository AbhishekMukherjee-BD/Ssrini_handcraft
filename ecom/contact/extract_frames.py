import cv2
import os
from PIL import Image

video_path = '/Users/abhishekmukherjee/Developer/antigravity/IDE/Jarvis/client/contact/Drone_shot_moving_inside_1080p_202608282042.mp4'
out_dir = '/Users/abhishekmukherjee/Developer/antigravity/IDE/Jarvis/client/contact/frames'
os.makedirs(out_dir, exist_ok=True)

cap = cv2.VideoCapture(video_path)
total_frames = int(cap.get(cv2.CAP_PROP_FRAME_COUNT))
fps = cap.get(cv2.CAP_PROP_FPS)
width = int(cap.get(cv2.CAP_PROP_FRAME_WIDTH))
height = int(cap.get(cv2.CAP_PROP_FRAME_HEIGHT))
print(f'Total frames: {total_frames}, FPS: {fps}, Size: {width}x{height}')

target_frames = 60
step = max(1, total_frames // target_frames)

count = 0
frame_idx = 0

while cap.isOpened():
    ret, frame = cap.read()
    if not ret:
        break
    if frame_idx % step == 0 and count < target_frames:
        count += 1
        # Convert BGR to RGB
        rgb_frame = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
        # Resize to max 1280 width for fast loading while keeping high quality
        h, w, _ = rgb_frame.shape
        new_w = 1280
        new_h = int(h * (new_w / w))
        img = Image.fromarray(rgb_frame).resize((new_w, new_h), Image.Resampling.LANCZOS)
        
        # Save as optimized WebP
        out_file = os.path.join(out_dir, f'frame_{count:03d}.webp')
        img.save(out_file, 'WEBP', quality=82)
    frame_idx += 1

cap.release()
print(f'Successfully extracted {count} WebP frames to {out_dir}')
